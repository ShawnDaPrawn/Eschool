import 'dart:math';
import 'package:eschool/app/routes.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/ui/widgets/customUserProfileImageWidget.dart';
import 'package:eschool/utils/homeBottomsheetMenu.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_svg/flutter_svg.dart';

class MoreMenuBottomsheetContainer extends StatelessWidget {
  const MoreMenuBottomsheetContainer({
    required this.onTapMoreMenuItemContainer, required this.closeBottomMenu, super.key,
  });
  final Function onTapMoreMenuItemContainer;
  final Function closeBottomMenu;

  Widget _buildMoreMenuContainer({
    required BuildContext context,
    required BoxConstraints boxConstraints,
    required String iconUrl,
    required String title,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 20),
      child: GestureDetector(
        onTap: () {
          onTapMoreMenuItemContainer(
            homeBottomSheetMenu.indexWhere((element) => element.title == title),
          );
        },
        child: Column(
          children: [
            Container(
              decoration: BoxDecoration(
                border: Border.all(
                  color: Theme.of(
                    context,
                  ).colorScheme.onSurface.withValues(alpha: 0.5),
                ),
                color: Theme.of(
                  context,
                ).colorScheme.onSecondary.withValues(alpha: 0.2),
                borderRadius: BorderRadius.circular(15),
              ),
              margin: EdgeInsets.symmetric(
                horizontal: boxConstraints.maxWidth * 0.065,
              ),
              width: boxConstraints.maxWidth * 0.2,
              height: boxConstraints.maxWidth * 0.2,
              padding: const EdgeInsets.all(12.5),
              child: SvgPicture.asset(iconUrl),
            ),
            const SizedBox(height: 10),
            SizedBox(
              width: boxConstraints.maxWidth * 0.3,
              child: Text(
                UiUtils.getTranslatedLabel(context, title),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: Theme.of(context).colorScheme.secondary,
                  fontSize: 14,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.only(top: 25, right: 25, left: 25),
      width: MediaQuery.sizeOf(context).width,
      decoration: BoxDecoration(
        color: Theme.of(context).scaffoldBackgroundColor,
        borderRadius: const BorderRadius.only(
          topLeft: Radius.circular(25),
          topRight: Radius.circular(25),
        ),
      ),
      child: LayoutBuilder(
        builder: (context, boxConstraints) {
          return Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Row(
                children: [
                  Container(
                    height: boxConstraints.maxWidth * 0.22,
                    width: boxConstraints.maxWidth * 0.22,
                    decoration: BoxDecoration(
                      border: Border.all(
                        width: 2,
                        color: Theme.of(context).colorScheme.onSurface,
                      ),
                      borderRadius: BorderRadius.circular(
                        boxConstraints.maxWidth * 0.11,
                      ),
                    ),
                    child: CustomUserProfileImageWidget(
                      profileUrl: context
                          .read<AuthCubit>()
                          .getStudentDetails()
                          .image,
                      color: Colors.black,
                    ),
                  ),
                  SizedBox(width: boxConstraints.maxWidth * 0.075),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Text(
                          context
                              .read<AuthCubit>()
                              .getStudentDetails()
                              .getFullName(),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: Theme.of(context).colorScheme.secondary,
                            fontWeight: FontWeight.w500,
                            fontSize: 18,
                          ),
                        ),
                        Row(
                          children: [
                            Flexible(
                              flex: 2,
                              child: Text(
                                '${UiUtils.getTranslatedLabel(context, classKey)}: ${context.read<AuthCubit>().getStudentDetails().classSectionNameWithSemester(context: context)}',
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w400,
                                  color: Theme.of(
                                    context,
                                  ).colorScheme.onSurface,
                                ),
                              ),
                            ),
                            const SizedBox(width: 10),
                            Container(
                              width: 1.5,
                              height: 12,
                              color: Theme.of(context).colorScheme.onSurface,
                            ),
                            const SizedBox(width: 10),
                            Flexible(
                              child: Text(
                                '${UiUtils.getTranslatedLabel(context, rollNoKey)}: ${context.read<AuthCubit>().getStudentDetails().rollNumber}',
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w400,
                                  color: Theme.of(
                                    context,
                                  ).colorScheme.onSurface,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsetsDirectional.only(end: 10),
                    child: InkWell(
                      onTap: () {
                        closeBottomMenu();
                        Navigator.of(context).pushNamed(
                          Routes.studentProfile,
                          arguments: context
                              .read<AuthCubit>()
                              .getStudentDetails(),
                        );
                      },
                      child: Transform.rotate(
                        angle: pi,
                        child: Icon(
                          Icons.arrow_back_ios,
                          color: Theme.of(context).colorScheme.secondary,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
              Divider(
                color: Theme.of(context).colorScheme.onSurface,
                height: 50,
              ),
              Wrap(
                children: homeBottomSheetMenu
                    .map(
                      (e) => _buildMoreMenuContainer(
                        context: context,
                        boxConstraints: boxConstraints,
                        iconUrl: e.iconUrl,
                        title: e.title,
                      ),
                    )
                    .toList(),
              ),
              SizedBox(height: UiUtils.getScrollViewBottomPadding(context)),
            ],
          );
        },
      ),
    );
  }
}
