import csv
import random
import re
from pathlib import Path


SYNONYMS = {
    "necesito": ["requiero", "me hace falta", "preciso"],
    "ayuda": ["apoyo", "asistencia"],
    "quiero": ["deseo", "me gustaría", "busco"],
    "no tengo": ["me falta", "estoy sin"],
    "donde": ["dónde"],
    "dónde": ["en qué lugar"],
    "urgente": ["con urgencia", "de manera urgente"],
    "necesito una": ["requiero una", "me hace falta una"],
}


def replace_synonyms(text, prob=0.35):
    # Replace some words with synonyms randomly
    for word, syns in SYNONYMS.items():
        # use regex word boundary, case-insensitive
        pattern = re.compile(r"\b" + re.escape(word) + r"\b", flags=re.IGNORECASE)

        def _repl(match):
            if random.random() < prob:
                choice = random.choice(syns)
                # preserve capitalization
                if match.group(0)[0].isupper():
                    return choice.capitalize()
                return choice
            return match.group(0)

        text = pattern.sub(_repl, text)
    return text


def add_prefix_suffix(text):
    prefixes = ["Por favor, ", "Buen día, ", "Hola, ", ""]
    suffixes = ["", ", gracias", ". Por favor ayúdenme.", "." ]
    return random.choice(prefixes) + text + random.choice(suffixes)


def shuffle_phrases(text):
    # Very small reordering: split by commas or ' y '
    parts = re.split(r",| y ", text)
    if len(parts) <= 1:
        return text
    random.shuffle(parts)
    joined = ", ".join(p.strip() for p in parts if p.strip())
    return joined


def generate_variants(text, n=3):
    variants = set()
    attempts = 0
    while len(variants) < n and attempts < n * 8:
        attempts += 1
        t = text
        # randomly apply transformations
        if random.random() < 0.6:
            t = replace_synonyms(t, prob=0.4)
        if random.random() < 0.4:
            t = shuffle_phrases(t)
        if random.random() < 0.5:
            t = add_prefix_suffix(t)
        # normalize spaces
        t = re.sub(r"\s+", " ", t).strip()
        if t and t.lower() != text.lower():
            variants.add(t)

    return list(variants)


def augment_csv(input_path, output_path, factor=3, seed=42):
    random.seed(seed)
    in_path = Path(input_path)
    out_path = Path(output_path)

    if not in_path.exists():
        raise FileNotFoundError(f"Input CSV not found: {in_path}")

    rows = []
    with in_path.open(newline='', encoding='utf-8') as f:
        reader = csv.DictReader(f)
        for r in reader:
            rows.append({'texto': r['texto'].strip(), 'dependencia': r['dependencia'].strip()})

    out_rows = []
    for r in rows:
        out_rows.append(r)
        # generate 'factor' variants per original
        variants = generate_variants(r['texto'], n=factor)
        for v in variants:
            out_rows.append({'texto': v, 'dependencia': r['dependencia']})

    # deduplicate by exact text + dependence
    seen = set()
    unique = []
    for r in out_rows:
        key = (r['texto'].lower(), r['dependencia'].lower())
        if key in seen:
            continue
        seen.add(key)
        unique.append(r)

    # write CSV
    with out_path.open('w', newline='', encoding='utf-8') as f:
        writer = csv.DictWriter(f, fieldnames=['texto','dependencia'])
        writer.writeheader()
        for r in unique:
            writer.writerow(r)

    return len(rows), len(unique)


if __name__ == '__main__':
    import argparse
    parser = argparse.ArgumentParser(description='Aumentar ejemplos CSV para dependencias')
    parser.add_argument('--input', default='ejemplos_dependencias.csv')
    parser.add_argument('--output', default='ejemplos_dependencias_aumentado.csv')
    parser.add_argument('--factor', type=int, default=3, help='Número de variantes por ejemplo')
    args = parser.parse_args()

    # Paths relative to this script
    base = Path(__file__).parent
    inp = base / args.input
    out = base / args.output

    orig, total = augment_csv(inp, out, factor=args.factor)
    print(f'Original rows: {orig}, Augmented rows: {total}')
