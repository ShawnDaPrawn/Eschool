---
sidebar_position: 3
---

# Change Package Name

##### 1. Unzip the downloaded code. After unzipping you will have E-School - Flutter Code zip folder. Unzip that folder and open it in Android Studio or Visual Studio Code.

##### 2. Open IDE terminal, go to your project path and execute command:
   ```
   flutter pub get
   ```
   
##### 3. If you are running this app for iOS then run these following commands in terminal:
   ```
   cd ios
   pod install
   cd ..
   ```
   
##### 4. Change package name of Android app:
   
   Execute this command in your terminal:
   ```
   flutter pub run change_app_package_name:main your_new_package_name
   ```
   
   ![Change Package Name](../static/images/app/changePackageName.png)
   
##### 5. Change package name of iOS app:
   
   Open iOS folder of this project in Xcode. Go Select Runner->Targets->General->Identity and enter new package name in Build Identifier.
   
   ![Change iOS Package Name](../static/images/app/changePackageName1.png) 