| Élément | Description / Rôle | Prix indicatif (TTC) | Lien d'achat (Kubii / Amazon) |
| :---                                | :---                                                       | :---           |
| **Raspberry Pi 5 (4 Go)**           | Carte mère principale (64 bits, 2,4 GHz).                  | ~ 136,00 €     |
| **Alimentation Officielle 27W**     | Pour délivrer les 5A requis.                               | ~ 13,00 €      |
| **Extension M.2 HAT+**              | À clipser pour connecter le SSD NVMe.                      | ~ 14,00 €      |
| **SSD NVMe M.2 (250 Go)**           | Stockage ultra-rapide dédié uniquement au SE.              | ~ 35,00 €      |
| **Boîtier Argon NEO 5 M.2**         | Boîtier en aluminium pour enfermer le Pi 5 et le SSD NVMe. | ~ 42,00 €      |
| **Boîtier USB 3.0 pour SSD 2,5"**   | Votre Crucial MX500 actuel sur le port USB 3 du Pi 5.      | ~ 10,00 €      |
| **TOTAL ESTIMÉ**                    |                    *Hors Crucial déjà possédé*             | **~ 250,00 €** |

# Migration YunoHost / Nextcloud : De l'Olimex A20 au Raspberry Pi 5 (SSD NVMe)

Ce document récapitule la configuration matérielle recommandée et la procédure pas-à-pas pour migrer votre serveur YunoHost depuis une carte Olimex 32 bits vers un Raspberry Pi 5 64 bits, tout en conservant votre SSD Crucial MX500 2,5" actuel pour le stockage des données.

---

## 🛒 1. La Configuration Matérielle

Pour cette configuration, le système YunoHost est installé sur un SSD NVMe ultra-rapide intégré au boîtier, tandis que votre SSD Crucial MX500 existant est placé dans un boîtier USB externe pour accueillir vos données déportées.

| Élément | Description / Rôle | Prix indicatif (TTC) | Lien d'achat (Kubii / Amazon) |
| :---                                | :---                                                       | :---           |
| **Raspberry Pi 5 (4 Go)**           | Carte mère principale (64 bits, 2,4 GHz).                  | ~ 136,00 €     |
| **Alimentation Officielle 27W**     | Pour délivrer les 5A requis.                               | ~  13,00 €     |
| **Extension M.2 HAT+**              | À clipser pour connecter le SSD NVMe.                      | ~  14,00 €     |
| **SSD NVMe M.2 (250 Go)**           | Stockage ultra-rapide dédié uniquement au SE.              | ~  35,00 €     |
| **Boîtier Argon NEO 5 M.2**         | Boîtier en aluminium pour enfermer le Pi 5 et le SSD NVMe. | ~  42,00 €     |
| **Boîtier USB 3.0 pour SSD 2,5"**   | Votre Crucial MX500 actuel sur le port USB 3 du Pi 5.      | ~  10,00 €     |
| **TOTAL ESTIMÉ**                    |                    *Hors Crucial déjà possédé*             | **~ 250,00 €** |

---

## 🛠️ 2. Procédure Complète de Migration

### Étape 1 : Sauvegarde sur l'Olimex A20
Puisque vos données volumineuses sont sur le disque externe, YunoHost va sauvegarder la configuration générale et les pointeurs de vos applications. La sauvegarde sera stockée directement sur votre disque externe (via le dossier bind).

1. Connectez-vous en SSH à votre Olimex.
2. Lancez la création de la sauvegarde complète :
   ```bash
   sudo yunohost backup create --system --apps
   ```
3. Éteignez proprement votre carte Olimex :
   ```bash
   sudo poweroff
   ```
4. Débranchez votre SSD Crucial MX500 de l'Olimex et insérez-le dans son nouveau **boîtier externe USB 3.0**.

### Étape 2 : Préparation du support d'installation temporaire (Sur votre PC portable)
Cette méthode utilise une clé USB (ou carte MicroSD) comme intermédiaire pour installer le système sur le SSD NVMe vierge sans adaptateur coûteux.

1. Téléchargez l'image ISO officielle de **YunoHost pour Raspberry Pi 5** sur le [site officiel de YunoHost](https://yunohost.org).
2. Branchez une **clé USB classique** (ou une carte MicroSD) sur votre ordinateur portable.
3. Lancez le logiciel **Raspberry Pi Imager** sur votre PC portable.
4. Sélectionnez **Raspberry Pi 5** comme appareil.
5. Dans *Système d'exploitation*, descendez tout en bas, choisissez **"Utiliser image personnalisée"** (*Use custom*) et sélectionnez l'ISO de YunoHost téléchargée.
6. Dans *Stockage*, sélectionnez votre clé USB (ou carte MicroSD) temporaire.
7. Cliquez sur **Suivant** pour flasher le support. Une fois terminé, éjectez-le de votre PC.

### Étape 3 : Installation de YunoHost sur le SSD NVMe du Pi 5
1. Assemblez votre Raspberry Pi 5 dans son boîtier avec l'extension M.2 HAT+ et le **SSD NVMe vierge à l'intérieur**.
2. Branchez votre clé USB (ou MicroSD) flashée à l'Étape 2 sur le Pi 5.
3. Connectez le Pi 5 à un écran, un clavier et à votre box internet en Ethernet. **Ne branchez pas encore le disque USB Crucial.**
4. Allumez le Raspberry Pi 5. Il démarre sur le support temporaire et lance l'installateur graphique de YunoHost.
5. Lorsque l'installateur vous demande sur quel disque écrire le système, sélectionnez le **SSD NVMe interne** (généralement nommé `/dev/nvme0n1`).
6. Une fois l'installation terminée, éteignez le Pi 5 lorsqu'on vous le propose et **retirez la clé USB temporaire** (ou la MicroSD).

### Étape 4 : Liaison du disque Crucial MX500 sur le Pi 5
Avant de restaurer YunoHost, nous devons remonter le disque externe contenant vos anciennes données exactement sur le même chemin que l'ancienne carte (`/mnt/hdd`).

1. Branchez le boîtier USB contenant votre Crucial MX500 sur un **port USB 3.0 (bleu)** du Pi 5.
2. Allumez le Pi 5 et connectez-vous y en SSH (identifiants définis lors de l'installation à l'écran).
3. Créez le dossier qui servira de point de montage :
   ```bash
   sudo mkdir -p /mnt/hdd
   ```
4. Ouvrez le fichier de configuration des disques :
   ```bash
   sudo nano /etc/fstab
   ```
5. Ajoutez la ligne suivante tout en bas du fichier pour monter votre disque via son UUID persistant :
   ```text
   UUID="2d81bdfd-578a-4d63-a2c3-8e4feb0560f8" /mnt/hdd ext4 defaults,nofail 0 2
   ```
6. Sauvegardez et quittez (`Ctrl+X`, puis `Y`, puis `Entrée`).
7. Montez le disque immédiatement sans redémarrer :
   ```bash
   sudo mount -a
   ```

### Étape 5 : Restauration automatique du système
Le Pi 5 ayant désormais accès au disque dur déporté, il va pouvoir lire l'archive de sauvegarde qui s'y trouve.

1. Ouvrez votre navigateur internet et accédez à l’adresse IP locale de votre Raspberry Pi 5 (ex: `https://1.xx`).
2. Sur l'écran de Post-installation, choisissez l'option **"Restaurer une sauvegarde"** (*Restore a backup*).
3. Le système va automatiquement scanner le dossier `/mnt/hdd/home/yunohost.backup/archives/` (grâce au montage fstab), détecter votre archive de 10 Go et vous la proposer.
4. Lancez la restauration. YunoHost va réinstaller Nextcloud, recréer vos utilisateurs et réinjecter automatiquement les lignes de liaison (`bind`) dans le fichier `/etc/fstab` du Pi 5 pour lier définitivement les dossiers de données et de courriels au disque USB.

### Étape 6 : Finalisation et réseau
1. Une fois la restauration terminée, rendez-vous sur l'interface de votre box internet.
2. Modifiez la règle de bail DHCP statique (IP fixe) pour attribuer l'ancienne adresse IP de votre Olimex au Raspberry Pi 5.
3. Vos redirections de ports (80, 443, etc.) et votre nom de domaine pointeront instantanément vers votre nouvelle machine.
4. Vous pouvez débrancher l'écran et le clavier du Pi 5, il tournera désormais en tâche de fond de manière autonome et extrêmement rapide !