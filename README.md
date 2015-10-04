# Movable Type Single Page Installer

Movable Type SPI is single PHP script, which can download MTOS 5.13 ZIP package from Amazon S3 and unzip it to the same folder the script is. 

## How to use it

1. Download this repo by clicking "Download ZIP" button on right bottom corner of [this](https://github.com/masiuchi/mt-spi) page.
2. Unzip downloaded package somewhere on your computer.
3. Upload unzipped start.php (via FTP problably) to the folder on your server where you want Movable Type to be installed.
4. Go to the URL where Movable Type will run and add start.php behind the last slash. Example: `http://example.com/start.php`.

This installer is based on [Mautic SPI](https://github.com/mautic/mautic-spi).
