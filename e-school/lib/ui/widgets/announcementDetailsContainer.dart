import 'package:eschool/data/models/announcement.dart';
import 'package:eschool/ui/widgets/studyMaterialWithDownloadButtonContainer.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';

class AnnouncementDetailsContainer extends StatelessWidget {
  const AnnouncementDetailsContainer({required this.announcement, super.key});
  final Announcement announcement;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 20),
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(10),
      ),
      width: MediaQuery.sizeOf(context).width * 0.85,
      child: LayoutBuilder(
        builder: (context, boxConstraints) {
          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                announcement.title,
                style: TextStyle(
                  height: 1.2,
                  color: Theme.of(context).colorScheme.secondary,
                  fontSize: 15,
                  fontWeight: FontWeight.w500,
                ),
              ),
              SizedBox(height: announcement.description.isEmpty ? 0 : 5),
              announcement.description.isEmpty
                  ? const SizedBox()
                  : Text(
                      announcement.description,
                      style: TextStyle(
                        height: 1.2,
                        color: Theme.of(context).colorScheme.secondary,
                        fontWeight: FontWeight.w400,
                        fontSize: 11.5,
                      ),
                    ),
              ...announcement.files.map(
                (studyMaterial) => StudyMaterialWithDownloadButtonContainer(
                  boxConstraints: boxConstraints,
                  studyMaterial: studyMaterial,
                ),
              ),
              SizedBox(height: announcement.files.isNotEmpty ? 0 : 5),
              Row(
                children: [
                  Icon(
                    Icons.calendar_month_outlined,
                    color: Theme.of(
                      context,
                    ).colorScheme.onSurface.withValues(alpha: 0.75),
                    size: 14,
                  ),
                  const SizedBox(width: 5),
                  Expanded(
                    child: Text(
                      UiUtils.getTimeAgo(context, date: announcement.createdAt),
                      style: TextStyle(
                        color: Theme.of(
                          context,
                        ).colorScheme.onSurface.withValues(alpha: 0.75),
                        fontWeight: FontWeight.w400,
                        fontSize: 10,
                      ),
                      textAlign: TextAlign.start,
                    ),
                  ),
                ],
              ),
            ],
          );
        },
      ),
    );
  }
}
