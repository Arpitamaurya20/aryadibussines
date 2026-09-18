import os
import glob

search_text = '<body class="desktop chrome webkit pace-done mobile-nav-on nav-function-fixed mobile-view-activated blur">'
replace_text = '<body class="mod-bg-1 header-function-fixed nav-function-fixed blur">'

directories = [
    r'c:\wamp64\www\projects\aryadibussines\admin'
]

count = 0
for directory in directories:
    for filepath in glob.glob(directory + '/**/*.php', recursive=True):
        try:
            with open(filepath, 'r', encoding='utf-8') as f:
                content = f.read()
            if search_text in content:
                content = content.replace(search_text, replace_text)
                with open(filepath, 'w', encoding='utf-8') as f:
                    f.write(content)
                count += 1
                print(f'Updated {filepath}')
        except Exception as e:
            pass
print(f'Total files updated: {count}')
