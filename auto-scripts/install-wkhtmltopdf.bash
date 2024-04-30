wget https://github.com/wkhtmltopdf/wkhtmltopdf/releases/download/0.12.4/wkhtmltox-0.12.4_linux-generic-amd64.tar.xz
mv wkhtmltox-0.12.4_linux-generic-amd64.tar.xz wkhtmlpdf
tar -xvf wkhtmlpdf
wkhtmltox/bin/wkhtmltopdf -V