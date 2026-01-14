// fix-scoped-css.js - Quita scoped y agrega namespace a todos los selectores
import fs from 'fs';
import process from 'process';

const filePath = process.argv[2];
if (!filePath) {
  console.error('Uso: node fix-scoped-css.js <archivo.vue>');
  process.exit(1);
}

let content = fs.readFileSync(filePath, 'utf8');

// Extraer el namespace del primer div del template
const templateMatch = content.match(/<template>\s*<div class="([^"]+)"/);
if (!templateMatch) {
  console.error('No se encontró un div raíz con clase en el template');
  process.exit(1);
}

const namespace = templateMatch[1];
console.log(`Namespace detectado: .${namespace}`);

// Reemplazar <style scoped> por <style>
content = content.replace('<style scoped>', '<style>');

// Agregar comentario explicativo
const styleMatch = content.match(/(<style>)([\s\S]*?)(<\/style>)/);
if (styleMatch) {
  const [, openTag, css, closeTag] = styleMatch;

  // Agregar namespace a cada selector CSS (excepto los que ya lo tienen)
  const lines = css.split('\n');
  const fixedLines = lines.map(line => {
    // Si es un selector CSS (contiene { y no es un comentario)
    if (line.includes('{') && !line.trim().startsWith('/*') && !line.trim().startsWith('//')) {
      // Extraer el selector
      const selectorMatch = line.match(/^(\s*)([\s\S]+?)(\s*\{)/);
      if (selectorMatch) {
        const [, indent, selector, bracket] = selectorMatch;

        // Si el selector ya contiene el namespace, no modificar
        if (selector.includes(`.${namespace}`)) {
          return line;
        }

        // Agregar namespace
        const selectors = selector.split(',').map(s => {
          s = s.trim();
          // No agregar namespace a selectores especiales
          if (s.startsWith('@') || s.startsWith(':root') || s.startsWith('*')) {
            return s;
          }
          return `.${namespace} ${s}`;
        }).join(',\n' + indent);

        return `${indent}${selectors}${bracket}`;
      }
    }
    return line;
  });

  const fixedCss = `\n/* Sin scoped - usando namespace .${namespace} para evitar conflictos */\n` + fixedLines.join('\n');
  content = content.replace(styleMatch[0], `${openTag}${fixedCss}${closeTag}`);
}

fs.writeFileSync(filePath, content, 'utf8');
console.log(`✅ Archivo actualizado: ${filePath}`);
