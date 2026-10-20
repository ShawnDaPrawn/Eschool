import 'package:eschool_teacher/ui/widgets/customBackButton.dart';
import 'package:eschool_teacher/ui/widgets/screenTopBackgroundContainer.dart';

import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';

class CustomAppBar extends StatelessWidget {
  const CustomAppBar({
    required this.title, super.key,
    this.onPressBackButton,
    this.showBackButton,
    this.subTitle,
    this.trailingWidget,
    this.actionButton,
  });
  final String title;
  final Function? onPressBackButton;
  final String? subTitle;
  final Widget? trailingWidget;
  final bool? showBackButton;
  final Widget? actionButton;

  @override
  Widget build(BuildContext context) {
    return ScreenTopBackgroundContainer(
      padding: const EdgeInsets.all(0),
      heightPercentage: UiUtils.appBarSmallerHeightPercentage,
      child: LayoutBuilder(
        builder: (context, boxConstraints) {
          return Stack(
            children: [
              if (showBackButton ?? true)
                CustomBackButton(
                  onTap: onPressBackButton,
                  alignmentDirectional: AlignmentDirectional.centerStart,
                )
              else
                const SizedBox(),
              Align(
                alignment: AlignmentDirectional.centerEnd,
                child: Padding(
                  padding: EdgeInsetsDirectional.only(
                    end: UiUtils.screenContentHorizontalPadding,
                  ),
                  child: trailingWidget,
                ),
              ),
              Align(
                child: Container(
                  alignment: Alignment.center,
                  width: boxConstraints.maxWidth * 0.6,
                  child: Text(
                    title,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: UiUtils.screenTitleFontSize,
                      color: Theme.of(context).scaffoldBackgroundColor,
                    ),
                  ),
                ),
              ),
              Align(
                child: Container(
                  alignment: Alignment.center,
                  width: boxConstraints.maxWidth * 0.6,
                  margin: EdgeInsets.only(
                    top: boxConstraints.maxHeight * 0.25 +
                        UiUtils.screenTitleFontSize,
                  ),
                  child: Text(
                    subTitle ?? '',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      fontSize: UiUtils.screenSubTitleFontSize,
                      color: Theme.of(context).scaffoldBackgroundColor,
                    ),
                  ),
                ),
              ),
              if (actionButton != null)
                Align(
                  alignment: AlignmentDirectional.centerEnd,
                  child: Padding(
                    padding: const EdgeInsetsDirectional.only(
                      end: 10,
                    ),
                    child: actionButton,
                  ),
                ),
            ],
          );
        },
      ),
    );
  }
}
