const express = require("express");
const cors = require("cors");
const fs = require("fs");
const path = require("path");

const {
    S3Client,
    ListObjectsV2Command,
    PutObjectCommand,
    DeleteObjectCommand
} = require("@aws-sdk/client-s3");

const { getSignedUrl } = require("@aws-sdk/s3-request-presigner");

// 🔥 API modular de firebase-admin (más estable entre versiones)
const { initializeApp, cert } = require("firebase-admin/app");
const { getMessaging } = require("firebase-admin/messaging");

const app = express();

const BUCKET = "galeriax";

// 🔥 Carpeta por defecto si el cliente no manda ninguna
const DEFAULT_FOLDER = "galeria/";

// 🔥 MIDDLEWARE
app.use(cors());
app.use(express.json({ limit: "25mb" }));

// 🔥 R2 CONFIG
const s3 = new S3Client({
    region: "auto",
    endpoint: process.env.R2_ENDPOINT,
    credentials: {
        accessKeyId: process.env.R2_ACCESS_KEY,
        secretAccessKey: process.env.R2_SECRET_KEY
    }
});

// 📱 TOKENS FCM (persistencia simple en archivo local)
const TOKENS_FILE = path.join(__dirname, "tokens.json");

function loadTokens() {
    try {
        return JSON.parse(fs.readFileSync(TOKENS_FILE, "utf8"));
    } catch {
        return [];
    }
}

function saveTokens(tokens) {
    fs.writeFileSync(TOKENS_FILE, JSON.stringify(tokens, null, 2));
}

// 🔥 FIREBASE ADMIN CONFIG (para mandar pushes reales)
let firebaseApp = null;
let firebaseReady = false;

try {
    if (process.env.FIREBASE_SERVICE_ACCOUNT_B64) {
        const raw = process.env.FIREBASE_SERVICE_ACCOUNT_B64.trim();
        const serviceAccountJson = Buffer.from(raw, "base64").toString("utf8");
        const serviceAccount = JSON.parse(serviceAccountJson);

        firebaseApp = initializeApp({
            credential: cert(serviceAccount)
        });

        firebaseReady = true;
        console.log("Firebase Admin inicializado ✅ (proyecto:", serviceAccount.project_id + ")");
    } else {
        console.log("⚠️ FIREBASE_SERVICE_ACCOUNT_B64 no configurada, /notify no funcionará");
    }
} catch (e) {
    console.log("⚠️ Error inicializando Firebase Admin:", e.message);
}

// 🧠 normaliza y sanea el prefijo de carpeta recibido del cliente
function resolveFolder(raw) {
    if (!raw) return DEFAULT_FOLDER;

    let folder = String(raw).trim();

    folder = folder.replace(/\.\./g, "").replace(/^\/+/, "");

    if (!folder.endsWith("/")) folder += "/";

    return folder || DEFAULT_FOLDER;
}

// 🧠 helper
function generateKey(folder) {
    return folder + "img_" + Date.now() + ".jpg";
}

// 🧪 TEST
app.get("/", (req, res) => {
    res.json({ ok: true, message: "API funcionando 🚀" });
});


// 📸 LISTAR
app.get("/list", async (req, res) => {
    try {

        const folder = resolveFolder(req.query.folder);

        const data = await s3.send(
            new ListObjectsV2Command({
                Bucket: BUCKET,
                Prefix: folder
            })
        );

        const baseUrl = "https://pub-23557c39f90d46d584f7e9b28f7dff3b.r2.dev";

        const urls = (data.Contents || [])
            .filter(f => f.Key && /\.(jpg|jpeg|png|webp)$/i.test(f.Key))
            .sort((a, b) => new Date(b.LastModified) - new Date(a.LastModified))
            .map(f => `${baseUrl}/${f.Key}`);

        res.json(urls);

    } catch (err) {
        console.log(err);
        res.status(500).json({ error: "Error listando R2" });
    }
});


// 🔐 SIGNED URL
app.get("/sign", async (req, res) => {
    try {

        const folder = resolveFolder(req.query.folder);
        const key = generateKey(folder);

        const command = new PutObjectCommand({
            Bucket: BUCKET,
            Key: key,
            ContentType: "image/jpeg"
        });

        const url = await getSignedUrl(s3, command, { expiresIn: 60 });

        res.json({ url, key });

    } catch (err) {
        console.log(err);
        res.status(500).json({ error: "error generando firma" });
    }
});


// 📤 UPLOAD FALLBACK
app.post("/upload", async (req, res) => {
    try {

        const { base64, folder: rawFolder } = req.body;

        if (!base64) {
            return res.status(400).json({ error: "sin imagen" });
        }

        const folder = resolveFolder(rawFolder);
        const buffer = Buffer.from(base64, "base64");

        const key = generateKey(folder);

        await s3.send(new PutObjectCommand({
            Bucket: BUCKET,
            Key: key,
            Body: buffer,
            ContentType: "image/jpeg"
        }));

        res.json({ ok: true, key });

    } catch (e) {
        console.log(e);
        res.status(500).json({ error: e.message });
    }
});


// 🗑 DELETE
app.get("/delete", async (req, res) => {
    try {

        let file = req.query.file;

        if (!file) {
            return res.status(400).json({ error: "sin file" });
        }

        const folder = resolveFolder(req.query.folder);

        file = file.split("/").pop().trim();

        const key = file.startsWith(folder)
            ? file
            : folder + file;

        await s3.send(new DeleteObjectCommand({
            Bucket: BUCKET,
            Key: key
        }));

        res.json({ ok: true, deleted: key });

    } catch (e) {
        console.log(e);
        res.status(500).json({ error: e.message });
    }
});


// 📱 REGISTRAR TOKEN FCM
app.post("/register-token", (req, res) => {
    try {
        const { token, device } = req.body;

        if (!token) {
            return res.status(400).json({ error: "sin token" });
        }

        const tokens = loadTokens();

        if (!tokens.some(t => t.token === token)) {
            tokens.push({ token, device: device || "desconocido", registeredAt: new Date().toISOString() });
            saveTokens(tokens);
        }

        res.json({ ok: true, total: tokens.length });

    } catch (e) {
        console.log(e);
        res.status(500).json({ error: e.message });
    }
});

// 📋 VER TOKENS REGISTRADOS
app.get("/tokens", (req, res) => {
    res.json(loadTokens());
});


// 🔔 MANDAR NOTIFICACIÓN A TODOS LOS TOKENS REGISTRADOS
app.post("/notify", async (req, res) => {
    try {
        if (!firebaseReady) {
            return res.status(503).json({ ok: false, error: "Firebase Admin no está configurado" });
        }

        const { titulo, mensaje } = req.body;

        if (!titulo || !mensaje) {
            return res.status(400).json({ ok: false, error: "faltan titulo o mensaje" });
        }

        const tokens = loadTokens();

        if (tokens.length === 0) {
            return res.json({ ok: true, enviados: 0, nota: "sin tokens registrados" });
        }

        const message = {
            notification: { title: titulo, body: mensaje },
            tokens: tokens.map(t => t.token)
        };

        const response = await getMessaging(firebaseApp).sendEachForMulticast(message);

        const tokensValidos = [];
        response.responses.forEach((r, i) => {
            if (r.success || !esTokenInvalido(r.error)) {
                tokensValidos.push(tokens[i]);
            }
        });

        if (tokensValidos.length !== tokens.length) {
            saveTokens(tokensValidos);
        }

        res.json({
            ok: true,
            enviados: response.successCount,
            fallidos: response.failureCount
        });

    } catch (e) {
        console.log(e);
        res.status(500).json({ ok: false, error: e.message });
    }
});

function esTokenInvalido(error) {
    if (!error) return false;
    const codigosInvalidos = [
        "messaging/invalid-registration-token",
        "messaging/registration-token-not-registered"
    ];
    return codigosInvalidos.includes(error.code);
}


// 🚀 SERVER
app.listen(3000, "0.0.0.0", () => {
    console.log("Servidor listo en puerto 3000 🚀");
});
