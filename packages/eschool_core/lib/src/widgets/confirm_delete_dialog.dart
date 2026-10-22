import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';

class ConfirmDeleteDialog extends StatelessWidget {
  const ConfirmDeleteDialog({super.key});

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      actions: [
        TextButton(
          child: Text(SharedUiUtils.getTranslatedLabel(context, yesKey)),
          onPressed: () {
            Navigator.of(context).pop(true);
          },
        ),
        TextButton(
          child: Text(SharedUiUtils.getTranslatedLabel(context, noKey)),
          onPressed: () {
            Navigator.of(context).pop();
          },
        ),
      ],
      content: Text(
        SharedUiUtils.getTranslatedLabel(context, deleteDialogMessageKey),
      ),
      title: Text(
        SharedUiUtils.getTranslatedLabel(context, deleteDialogTitleKey),
      ),
    );
  }
}
