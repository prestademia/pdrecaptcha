# pdrecaptcha created by Prestademia
Modulo recaptcha v3 invisible para el formulario de contacto compatible con prestashop 1.6 y 1.7

Advertencia!

Antes de instalar el módulo revise sí existe el archivo ContactController.php dentro la ruta override/controllers/front/ de tu tienda prestashop, entonces después de revisar la ruta tienes dos opciones.

1. Si existe el archivo, entonces debes descomprimir el modulo descargado y eliminar la carpeta override, además debes agregar en el archivo encontrado llamado ContactController.php, la siguiente linea de código dentro de la function postProcess:

        Hook::exec('actionBeforeContactSubmit');

2. Si no existe el archivo, puede continuar la instalación con normalidad.
