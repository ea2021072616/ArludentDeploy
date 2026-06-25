import React from "react";
import { createRoot } from "react-dom/client";
import App from "./App";
import "./index.css";
import { exportFhir, exportImage, importStatus, collectExportPayload } from "./odontogram";

// State para modo solo lectura
let isReadOnly = false;

// Configurar receptor de mensajes para integración con Iframe
window.addEventListener("message", async (event) => {
  const data = event.data;
  if (!data || !data.type) return;

  switch (data.type) {
    case "IMPORT_STATE":
      console.log("React received IMPORT_STATE:", data);
      if (data.payload) {
        let payloadObj = data.payload;
        // Parse repeatedly if the payload is double-stringified
        while (typeof payloadObj === "string") {
          try { 
            let parsed = JSON.parse(payloadObj);
            // If parsing it didn't change it (e.g. it's just a regular string), break to avoid infinite loop
            if (parsed === payloadObj) break;
            payloadObj = parsed;
          } catch(e) {
            break;
          }
        }
        console.log("Calling importStatus with:", payloadObj);
        importStatus(payloadObj);
      }
      if (data.readOnly !== undefined) {
        isReadOnly = data.readOnly;
        // Re-render para aplicar readOnly
        renderApp();
      }
      break;

    case "REQUEST_EXPORT":
      try {
        const state = collectExportPayload();
        const image = await exportImage("png", false);
        window.parent.postMessage({
          type: "EXPORT_RESULT",
          payload: state,
          image: image
        }, "*");
      } catch (error) {
        console.error("Error al exportar:", error);
      }
      break;
  }
});

const rootEl = document.getElementById("root");
let root: any = null;

function renderApp() {
  if (rootEl) {
    if (!root) root = createRoot(rootEl);
    root.render(
      <React.StrictMode>
        <App enableNotes language="es" readOnly={isReadOnly} />
      </React.StrictMode>
    );
  }
}

renderApp();

// Avisar al padre que el iframe está listo
window.parent.postMessage({ type: "ODONTOGRAM_READY" }, "*");
