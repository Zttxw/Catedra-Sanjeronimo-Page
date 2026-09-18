# 🏛️ Cátedra San Jerónimo — Primer Encuentro de Identidad Cultural

Sistema web oficial para el **Primer Encuentro de Identidad Cultural "Cátedra San Jerónimo"**, organizado por la **Subgerencia de Educación, Cultura y Deporte** — Gerencia de Desarrollo Social de la Municipalidad Distrital de San Jerónimo, Cusco, Perú.

---

## 📁 Estructura del Proyecto

```text
Catedra-Sanjeronimo-Page/
├── index.html            ← Landing page principal, formulario de registro, ticket voucher e impresión.
├── admin.html            ← Panel administrativo (Lista general, Control/Puerta en tiempo real, Importación masiva, CSV).
├── server.py             ← API backend en Python para almacenamiento centralizado y endpoints REST.
├── inscripciones.json    ← Base de datos en formato JSON con registros formateados en MAYÚSCULAS.
├── .cpanel.yml           ← Archivo de automatización de despliegue para cPanel Git™ Version Control.
├── .gitignore            ← Exclusión de carpetas temporales y logs.
└── imagenes/             ← Logos institucionales (MDSJ, Escudo) y galería fotográfica.
    ├── Logos/
    ├── Principal/
    └── Galeria/
```

---

## 🔐 Credenciales del Panel Administrativo

- **URL de acceso**: `https://sistema01.munisanjeronimocusco.gob.pe/catedra/admin.html`
- **Usuario**: `admin` (o `catedra`)
- **Contraseña**: `+Muni2026*`

---

## 🚀 Guía Completa de Despliegue en cPanel

### **Opción 1: Crear / Clonar con cPanel Git™ Version Control (Recomendado)**

1. Inicia sesión en tu **cPanel**.
2. Ve a la herramienta **Git™ Version Control**.
3. Haz clic en **Create** y completa los siguientes campos:
   - **Clone a Repository**: `Activado (Enabled)`
   - **Clone URL**: `https://github.com/Zttxw/Catedra-Sanjeronimo-Page.git`
   - **Repository Path**: `public_html/catedra`
   - **Repository Name**: `catedra`
4. Haz clic en el botón **Create**.

---

### **Opción 2: Despliegue y Actualización Instantánea vía Terminal de cPanel**

Si ya clonaste el repositorio o deseas actualizar el código desde GitHub a producción en 1 segundo:

1. Abre la **Terminal** en cPanel.
2. Copia, pega el siguiente comando completo y presiona `Enter`:

```bash
chmod 755 /home/sistema01/public_html/catedra && cd /home/sistema01/public_html/catedra && git pull origin main && find /home/sistema01/public_html/catedra -type d -exec chmod 755 {} + && find /home/sistema01/public_html/catedra -type f -exec chmod 644 {} +
```

---

### ⚠️ Permisos de Linux en cPanel (Solución a Error 403 Forbidden)

Si en algún momento el servidor muestra **403 Forbidden**, se debe a que la carpeta se creó con permisos restrictivos `0700`.

- **Carpetas**: Permiso `0755` (`drwxr-xr-x`)
- **Archivos**: Permiso `0644` (`-rw-r--r--`)

Para corregirlo desde el **Administrador de Archivos** de cPanel:
1. Clic derecho sobre la carpeta `catedra` en `public_html`.
2. Seleccionar **Change Permissions**.
3. Establecer en **`0755`** y guardar.

---

## 🔄 ¿Cómo publicar actualizaciones futuras de GitHub a cPanel?

Cada vez que realices mejoras locales y las subas a GitHub (`git push`), para actualizar tu servidor cPanel sólo debes:

1. Abrir la **Terminal** de cPanel y ejecutar:
   ```bash
   cd public_html/catedra && git pull origin main
   ```
2. **O alternativamente**, ir a **Git™ Version Control** en cPanel ➔ Seleccionar `catedra` ➔ **Manage** ➔ **Pull or Deploy** ➔ Clic en **Update from Remote** y **Deploy HEAD Commit**.

---

© 2026 Municipalidad Distrital de San Jerónimo — Cusco, Perú. *"Memoria, fe e identidad"*.  
Desarrollado por la **Oficina de Tecnologías de la Información (OTI)**.

<img width="1902" height="963" alt="image" src="https://github.com/user-attachments/assets/74a06496-31c4-4e65-9819-2f26322b32ff" />

<img width="1902" height="963" alt="image" src="https://github.com/user-attachments/assets/2b63ed06-3e7f-465b-b91d-aa194dd4d67a" />

<img width="1902" height="963" alt="image" src="https://github.com/user-attachments/assets/4eb9f1d7-f556-4dcc-aa40-776d1f8cf9a2" />



