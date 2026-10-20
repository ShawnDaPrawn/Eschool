import 'package:eschool/ui/widgets/customCloseButton.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';

class BottomsheetTopTitleAndCloseButton extends StatelessWidget {
  const BottomsheetTopTitleAndCloseButton({
    required this.onTapCloseButton, required this.titleKey, super.key,
  });
  final String titleKey;
  final Function onTapCloseButton;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              UiUtils.getTranslatedLabel(context, titleKey),
              style: TextStyle(
                fontSize: 16,
                color: Theme.of(context).colorScheme.secondary,
                fontWeight: FontWeight.w600,
              ),
            ),
            const Spacer(),
            CustomCloseButton(onTapCloseButton: onTapCloseButton),
          ],
        ),
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 10),
          child: Divider(color: Theme.of(context).colorScheme.onSurface),
        ),
        SizedBox(height: MediaQuery.sizeOf(context).height * 0.0075),
      ],
    );
  }
}
