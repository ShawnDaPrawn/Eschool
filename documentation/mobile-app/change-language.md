---
sidebar_position: 11
---

# Change Language

## Default Language

By default, the application language is English. To change the default language:

1. Go to `lib/utils/appLanguages.dart`
2. Add your respective language's code
3. Get your language code from [Google's Language Codes](https://developers.google.com/admin-sdk/directory/v1/languages)

![Change Default Language](../static/images/app/changeDefaultLanguage.png)

## Adding New Language

If your default language code is not in the app language list:

1. Add language details in the list as shown below
2. Go to `lib/utils/appLanguages.dart`

![Add Language](../static/images/app/addLanguage.png)

## Creating Language File

If your default language is not in `assets/languages/[language-code].json`:

1. Create a new file in the same folder with `[language-code].json`
2. Copy all labels from `en.json`
3. Convert label values to your respective language

![Add New Language](../static/images/app/addNewLanguage.jpg)
![Add New Language 2](../static/images/app/addNewLanguage2.jpg)
