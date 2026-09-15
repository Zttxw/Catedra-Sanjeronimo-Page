#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Servidor Centralizado Cátedra San Jerónimo 2026
Proporciona API REST + Servidor HTTP Estático en puerto 8080.
Mantiene la base de datos centralizada en 'inscripciones.json'.
"""

import os
import json
import datetime
from http.server import SimpleHTTPRequestHandler, HTTPServer
from urllib.parse import parse_qs, urlparse

DB_FILE = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'inscripciones.json')

def load_db():
    if not os.path.exists(DB_FILE):
        return []
    try:
        with open(DB_FILE, 'r', encoding='utf-8') as f:
            return json.load(f)
    except Exception as e:
        print(f"[ERROR] Error al leer {DB_FILE}: {e}")
        return []

def save_db(data):
    try:
        temp_file = DB_FILE + '.tmp'
        with open(temp_file, 'w', encoding='utf-8') as f:
            json.dump(data, f, ensure_ascii=False, indent=2)
        os.replace(temp_file, DB_FILE)
        return True
    except Exception as e:
        print(f"[ERROR] Error al guardar {DB_FILE}: {e}")
        return False

class CentralizedHandler(SimpleHTTPRequestHandler):
    def send_json(self, data, status=200):
        body = json.dumps(data, ensure_ascii=False).encode('utf-8')
        self.send_response(status)
        self.send_header('Content-Type', 'application/json; charset=utf-8')
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, DELETE')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type')
        self.send_header('Content-Length', str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def do_OPTIONS(self):
        self.send_response(200)
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, DELETE')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type')
        self.end_headers()

    def do_GET(self):
        parsed = urlparse(self.path)
        path = parsed.path.lower()
        query = parse_qs(parsed.query)

        if 'inscripciones' in path or query.get('endpoint', [''])[0] == 'inscripciones':
            records = load_db()
            self.send_json(records)
            return

        # Servir archivos estáticos por defecto
        super().do_GET()

    def do_POST(self):
        parsed = urlparse(self.path)
        path = parsed.path.lower()
        query = parse_qs(parsed.query)
        endpoint = query.get('endpoint', [''])[0]
        length = int(self.headers.get('Content-Length', 0))
        body_bytes = self.rfile.read(length) if length > 0 else b''

        try:
            body_json = json.loads(body_bytes.decode('utf-8')) if body_bytes else {}
        except Exception:
            body_json = {}

        db = load_db()

        # 1. Nueva Inscripción
        if 'inscripciones' in path or endpoint == 'inscripciones':
            nombres = str(body_json.get('nombres', '')).strip().upper()
            dni = str(body_json.get('dni', '')).strip()
            celular = str(body_json.get('celular', '')).strip()
            institucion = str(body_json.get('institucion', '')).strip().upper()

            if not nombres or not dni:
                self.send_json({'success': False, 'error': 'datos_incompletos'}, 400)
                return

            # Verificar duplicado de DNI
            existente = next((r for r in db if str(r.get('dni', '')).strip() == dni), None)
            if existente:
                self.send_json({
                    'success': False,
                    'error': 'duplicate_dni',
                    'existente': existente
                }, 409)
                return

            # Crear nuevo registro
            nuevo_codigo = f"CSJ-2026-{len(db) + 1:04d}"
            nuevo_registro = {
                'codigo': nuevo_codigo,
                'nombres': nombres,
                'dni': dni,
                'celular': celular if celular else '900000000',
                'institucion': institucion if institucion else 'SAN JERÓNIMO',
                'asistencia': 'Ambos días (21 y 22 de setiembre)',
                'attDay1': False,
                'attDay2': False,
                'registradoEl': datetime.datetime.now().strftime('%d/%m/%Y, %I:%M:%S %p')
            }

            db.append(nuevo_registro)
            save_db(db)
            self.send_json({'success': True, 'data': nuevo_registro}, 201)
            return

        # 2. Actualizar Asistencia
        elif 'asistencia' in path or endpoint == 'asistencia':
            codigo = str(body_json.get('codigo', '')).strip()
            field = str(body_json.get('field', '')).strip()
            val = body_json.get('val', None)

            found = next((r for r in db if str(r.get('codigo', '')).strip().lower() == codigo.lower() or str(r.get('dni', '')).strip() == codigo), None)
            if not found:
                self.send_json({'success': False, 'error': 'no_encontrado'}, 404)
                return

            if field in ['attDay1', 'attDay2']:
                found[field] = bool(val) if val is not None else not found.get(field, False)
            elif 'attDay1' in body_json or 'attDay2' in body_json:
                if 'attDay1' in body_json: found['attDay1'] = bool(body_json['attDay1'])
                if 'attDay2' in body_json: found['attDay2'] = bool(body_json['attDay2'])

            save_db(db)
            self.send_json({'success': True, 'data': found})
            return

        # 3. Importación Masiva
        elif 'importar' in path or endpoint == 'importar':
            items = body_json.get('items', [])
            count = 0
            for item in items:
                nombres = str(item.get('nombres', '')).strip().upper()
                dni = str(item.get('dni', '')).strip()
                if nombres and dni:
                    if not any(r.get('dni') == dni for r in db):
                        count += 1
                        nuevo_registro = {
                            'codigo': f"CSJ-2026-{len(db) + 1:04d}",
                            'nombres': nombres,
                            'dni': dni,
                            'celular': str(item.get('celular', '900000000')).strip(),
                            'institucion': str(item.get('institucion', 'SAN JERÓNIMO')).strip().upper(),
                            'asistencia': 'Ambos días (21 y 22 de setiembre)',
                            'attDay1': False,
                            'attDay2': False,
                            'registradoEl': datetime.datetime.now().strftime('%d/%m/%Y, %I:%M:%S %p')
                        }
                        db.append(nuevo_registro)
            save_db(db)
            self.send_json({'success': True, 'count': count, 'total': len(db)})
            return

        # 4. Eliminar Registro
        elif 'eliminar' in path or endpoint == 'eliminar':
            codigo = str(body_json.get('codigo', '')).strip()
            db = [r for r in db if str(r.get('codigo', '')).strip() != codigo]
            save_db(db)
            self.send_json({'success': True, 'total': len(db)})
            return

        self.send_json({'success': False, 'error': 'endpoint_no_valido'}, 404)

def run(port=8080):
    server_address = ('', port)
    httpd = HTTPServer(server_address, CentralizedHandler)
    print(f"🚀 Servidor Centralizado Cátedra San Jerónimo iniciado en puerto {port}...")
    print(f"📄 Archivo de Base de Datos: {DB_FILE}")
    httpd.serve_forever()

if __name__ == '__main__':
    run()
