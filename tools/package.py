"""Baut dist/bb-reviews-<version>.zip (Ordner bb-reviews/ im Archiv) und gibt die Version aus."""
import os, re, sys, zipfile
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SRC = os.path.join(ROOT, 'bb-reviews')
main = open(os.path.join(SRC, 'bb-reviews.php'), encoding='utf-8').read()
header = re.search(r'^\s*\*\s*Version:\s*(\d+\.\d+\.\d+)\s*$', main, re.M)
const = re.search(r"const VERSION = '(\d+\.\d+\.\d+)';", main)
if not header or not const or header.group(1) != const.group(1):
    sys.exit('Version im Plugin-Kopf und in BB_Reviews::VERSION stimmen nicht überein.')
version = header.group(1)
os.makedirs(os.path.join(ROOT, 'dist'), exist_ok=True)
target = os.path.join(ROOT, 'dist', 'bb-reviews-%s.zip' % version)
with zipfile.ZipFile(target, 'w', zipfile.ZIP_DEFLATED) as z:
    for folder, _, names in sorted(os.walk(SRC)):
        for name in sorted(names):
            if name.startswith('.'):
                continue
            path = os.path.join(folder, name)
            z.write(path, os.path.join('bb-reviews', os.path.relpath(path, SRC)))
print(version)
