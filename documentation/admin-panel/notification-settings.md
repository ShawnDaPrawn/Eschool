---
sidebar_position: 7
---

# Notification Settings

The Sender Id will be used for sending App Notification.

![Notification Settings](../static/images/admin/10.png)

## Sender Id

![Firebase Sender ID](../static/images/admin/firebase_sender_id.png)

1. In the Firebase console, go to Project Settings.
2. From Cloud Messaging tab check the Firebase Cloud Messaging API (V1) where Sender Id is given, add that to Admin Panel in System Settings > Notification Setting.

## Firebase Project Id

![Firebase Project ID](../static/images/admin/firebase_project_id.png)

1. In the Firebase console, go to Project Settings
2. Go to > General where Project Id is given add that to Admin Panel in System Settings > Notification Setting.

## Service Json File

![Firebase Service File](../static/images/admin/firebase_service_file.png)

1. In the Firebase console, open Settings > Service Accounts.
2. Click Generate New Private Key, then confirm by clicking Generate Key.
3. Securely store the JSON file containing the key and upload in Admin Panel in System Settings > Notification Setting. 