import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';

class ConfirmDeleteDialog extends StatelessWidget {
  const ConfirmDeleteDialog({super.key});

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      actions: [
        TextButton(
          child: Text(UiUtils.getTranslatedLabel(context, yesKey)),
          onPressed: () {
            Navigator.of(context).pop(true);
          },
        ),
        TextButton(
          child: Text(UiUtils.getTranslatedLabel(context, noKey)),
          onPressed: () {
            Navigator.of(context).pop();
          },
        ),
      ],
      content: Text(
        UiUtils.getTranslatedLabel(context, deleteDialogMessageKey),
      ),
      title: Text(UiUtils.getTranslatedLabel(context, deleteDialogTitleKey)),
    );
  }
}
