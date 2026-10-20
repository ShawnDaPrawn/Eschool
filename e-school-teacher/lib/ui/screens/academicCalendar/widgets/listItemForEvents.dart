import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/data/models/event.dart';
import 'package:eschool_teacher/ui/styles/colors.dart';
import 'package:eschool_teacher/ui/widgets/customImageWidget.dart';
import 'package:eschool_teacher/ui/widgets/customRoundedButton.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:readmore/readmore.dart';

class EventItemContainer extends StatelessWidget {
  const EventItemContainer({
    required this.event,
    super.key,
    this.isDetailsScreen = false,
    this.backgroundColor,
  });
  final Event event;
  final bool isDetailsScreen;
  final Color? backgroundColor;

  Text _dateTimeBuilder(BuildContext context) {
    return Text.rich(
      TextSpan(
        children: [
          if (event.startDate != null) ...[
            WidgetSpan(
              alignment: PlaceholderAlignment.middle,
              child: Padding(
                padding: const EdgeInsetsDirectional.only(end: 2),
                child: Icon(
                  Icons.calendar_month_outlined,
                  color: Theme.of(context).colorScheme.secondary,
                  size: 12,
                ),
              ),
            ),
            TextSpan(text: UiUtils.formatDate(event.startDate!)),
          ],
          if (event.endDate != null) ...[
            const WidgetSpan(
              alignment: PlaceholderAlignment.middle,
              child: SizedBox(width: 5),
            ),
            TextSpan(text: UiUtils.getTranslatedLabel(context, toKey)),
            const WidgetSpan(
              alignment: PlaceholderAlignment.middle,
              child: SizedBox(width: 5),
            ),
            WidgetSpan(
              alignment: PlaceholderAlignment.middle,
              child: Padding(
                padding: const EdgeInsetsDirectional.only(end: 2),
                child: Icon(
                  Icons.calendar_month_outlined,
                  color: Theme.of(context).colorScheme.secondary,
                  size: 12,
                ),
              ),
            ),
            TextSpan(text: UiUtils.formatDate(event.endDate!)),
          ],
          const WidgetSpan(
            alignment: PlaceholderAlignment.middle,
            child: SizedBox(width: 15),
          ),
          if (event.isSingleDayEvent &&
              event.startTime != null &&
              event.endTime != null) ...[
            WidgetSpan(
              alignment: PlaceholderAlignment.middle,
              child: Padding(
                padding: const EdgeInsetsDirectional.only(end: 2),
                child: Icon(
                  CupertinoIcons.clock,
                  color: Theme.of(context).colorScheme.secondary,
                  size: 12,
                ),
              ),
            ),
            TextSpan(
              text:
                  '${UiUtils.formatTime(event.startTime!)} ${UiUtils.getTranslatedLabel(context, toKey)} ${UiUtils.formatTime(event.endTime!)}',
            ),
          ],
        ],
        style: TextStyle(
          height: 1,
          color: Theme.of(context).colorScheme.secondary,
          fontWeight: FontWeight.w400,
          fontSize: 10,
        ),
      ),
      textAlign: TextAlign.start,
      maxLines: 1,
      overflow: TextOverflow.ellipsis,
    );
  }

  @override
  Widget build(BuildContext context) {
    final bool hasDetails =
        !isDetailsScreen && !event.isSingleDayEvent && event.hasDaySchedule;
    final onTap = hasDetails
        ? () {
            Navigator.pushNamed(
              context,
              Routes.eventDetails,
              arguments: {'event': event},
            );
          }
        : null;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.only(bottom: 20),
        decoration: BoxDecoration(
          color: backgroundColor ?? Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(10),
        ),
        width: double.maxFinite,
        clipBehavior: Clip.antiAlias,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            if (event.image?.isNotEmpty ?? false)
              Container(
                constraints: const BoxConstraints(maxHeight: 200),
                width: double.maxFinite,
                child: CustomImageWidget(imagePath: event.image ?? ''),
              ),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _dateTimeBuilder(context),
                  const SizedBox(height: 10),
                  Text(
                    event.title,
                    style: TextStyle(
                      height: 1,
                      color: Theme.of(context).colorScheme.secondary,
                      fontWeight: FontWeight.w600,
                      fontSize: 16,
                    ),
                    textAlign: TextAlign.start,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 5),
                  ReadMoreText(
                    event.description,
                    trimLines: isDetailsScreen ? 10 : 3,
                    colorClickableText: primaryColor,
                    trimMode: TrimMode.Line,
                    trimCollapsedText: UiUtils.getTranslatedLabel(
                      context,
                      showMoreKey,
                    ),
                    trimExpandedText:
                        ' ${UiUtils.getTranslatedLabel(context, showLessKey)}',
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.onSurface,
                      fontWeight: FontWeight.w400,
                      fontSize: 12,
                    ),
                    textAlign: TextAlign.start,
                  ),
                  if (hasDetails) ...[
                    const SizedBox(height: 10),
                    CustomRoundedButton(
                      textSize: 16,
                      height: null,
                      radius: 4,
                      backgroundColor: primaryColor,
                      buttonTitle: UiUtils.getTranslatedLabel(
                        context,
                        viewDetailsKey,
                      ),
                      showBorder: false,
                      textAlign: TextAlign.start,
                      padding: const EdgeInsets.symmetric(
                        horizontal: 15,
                        vertical: 5,
                      ),
                      alignment: null,
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
