import 'package:eschool_teacher/data/models/holiday.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';

class HolidayContainer extends StatelessWidget {
  const HolidayContainer({required this.holiday, super.key});
  final Holiday holiday;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 15),
      padding: const EdgeInsets.symmetric(horizontal: 15, vertical: 10),
      width: MediaQuery.sizeOf(context).width * 0.85,
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surfaceContainerLow,
        borderRadius: BorderRadius.circular(10),
      ),
      child: LayoutBuilder(
        builder: (context, boxConstraints) {
          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                holiday.title,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: Theme.of(context).colorScheme.onSurface,
                  fontWeight: FontWeight.w600,
                  fontSize: 14,
                ),
              ),
              SizedBox(height: holiday.description.isEmpty ? 0 : 5),
              if (holiday.description.isEmpty)
                const SizedBox()
              else
                Text(
                  holiday.description,
                  style: TextStyle(
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                    fontSize: 11.5,
                  ),
                ),
              const SizedBox(height: 5),
              Text.rich(
                TextSpan(
                  children: [
                    WidgetSpan(
                      alignment: PlaceholderAlignment.middle,
                      child: Padding(
                        padding: const EdgeInsetsDirectional.only(end: 2),
                        child: Icon(
                          Icons.calendar_month_outlined,
                          color: Theme.of(context).colorScheme.onSurface,
                          size: 12,
                        ),
                      ),
                    ),
                    TextSpan(text: UiUtils.formatDate(holiday.date)),
                  ],
                  style: TextStyle(
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                    fontSize: 12,
                  ),
                ),
                textAlign: TextAlign.start,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          );
        },
      ),
    );
  }
}
