---
sidebar_position: 14
---

# Chat Message Constraints

Within our messaging system, specific technical constraints are in place for effective functionality. When composing a regular text message, users are limited to a maximum of 500 characters. For file and image attachments, the system allows for a total of 30 files to be sent simultaneously, with an individual file size limit of 10 MB.

It's crucial to note that attempts to upload a file exceeding the 10 MB threshold will be unsuccessful, in adherence to these constraints. Modification of these parameters can be executed through the lib/utils/constants.dart file.

However, it's essential to acknowledge the backend's overarching limitations. The backend enforces a maximum cap of 50 files per message and restricts individual file sizes to a maximum of 2 GB. Consequently, any adjustments made within the app must align with these backend constraints for seamless operation.

![Chat Message Constraints](../static/images/app/chatMessageConstraints.png)
