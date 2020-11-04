import os
import shutil
def copy_tree_overwrite(src, dest):
    if os.path.exists(dest):
        shutil.rmtree(dest)
    shutil.copytree(src, dest)

def copy_file_overwrite(src, dest):
    if os.path.exists(dest):
        os.remove(dest)
    shutil.copyfile(src, dest) 

def setup():
    if os.environ.get('TRAVIS', False):
        USER, PASSWORD = 'travis', ''

    config = {}    
    config['DATABASE_NAME'] = 'test'
    config['DATABASE_USER'] = os.environ.get('DATABASE_USER', 'test')
    config['DATABASE_PASSWORD'] = os.environ.get('DATABASE_PASSWORD', 'test')
    config['DATABASE_HOST'] = os.environ.get('DATABASE_HOST', 'localhost')
    config['BOT_TOKEN'] = os.environ.get('BOT_TOKEN')
    config['BOT_ADMIN_SECRET'] = os.environ.get('BOT_ADMIN_SECRET')
    
    SSH_REMOTE = os.environ.get('SSH_REMOTE')
    SSH_PORT = os.environ.get('SSH_PORT')

    PUBLIC_HTML_PATH = os.environ.get('PUBLIC_HTML_PATH', '/var/www/html/')
    PUBLIC_HTML_HOST = os.environ.get('PUBLIC_HTML_HOST', 'localhost')

    PUBLIC_SUBFOLDER = 'telegram-live-chat'
    if not os.path.exists(os.path.join( PUBLIC_HTML_PATH, PUBLIC_SUBFOLDER)):
        os.mkdir(os.path.join( PUBLIC_HTML_PATH, PUBLIC_SUBFOLDER))
    
    BACKEND_PATH = os.path.join( PUBLIC_HTML_PATH, PUBLIC_SUBFOLDER, 'backend')
    FRONTEND_PATH = os.path.join( PUBLIC_HTML_PATH, PUBLIC_SUBFOLDER, 'frontend')
    BACKEND_TMP_PATH = os.path.join( '/var/tmp', PUBLIC_SUBFOLDER, 'backend')
    FRONTEND_TMP_PATH = os.path.join( '/var/tmp', PUBLIC_SUBFOLDER, 'frontend')
    
    copy_tree_overwrite('../backend', BACKEND_TMP_PATH)  
    copy_tree_overwrite('../frontend',FRONTEND_TMP_PATH )  

    CONFIG_PATH = os.path.join(BACKEND_TMP_PATH, 'livechat-config.php')
    copy_file_overwrite(os.path.join(BACKEND_TMP_PATH, 'livechat-config-sample.php'),
    CONFIG_PATH)

    config_text = ''
    with open(CONFIG_PATH) as f:
        config_text = f.read()
    for key, value in config.items():
        print (value)
        config_text = config_text.replace('<{}>'.format(key), value)
    with open(CONFIG_PATH, 'w') as f:
        f.write(config_text)
    os.system("rsync -arvz -e 'ssh -p {}' {} {}:{}".format(SSH_PORT ,BACKEND_TMP_PATH, SSH_REMOTE, os.path.join( PUBLIC_HTML_PATH, PUBLIC_SUBFOLDER)))
    #os.system("rsync -arvz -e 'ssh -p {}' {} {}:{}".format(SSH_PORT ,FRONTEND_TMP_PATH, SSH_REMOTE, os.path.join( PUBLIC_HTML_PATH, PUBLIC_SUBFOLDER)))

    

    

    
