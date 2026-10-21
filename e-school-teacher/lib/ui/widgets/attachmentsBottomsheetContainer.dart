import 'package:eschool_teacher/data/models/studyMaterial.dart';
import 'package:eschool_teacher/ui/widgets/announcementAttachmentContainer.dart';
import 'package:eschool_teacher/ui/widgets/studyMaterialContainer.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';

class AttachmentBottomsheetContainer extends StatelessWidget {
  const AttachmentBottomsheetContainer({
    required this.studyMaterials,
    required this.fromAnnouncementsContainer,
    super.key,
  });
  final List<StudyMaterial> studyMaterials;
  final bool fromAnnouncementsContainer;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(UiUtils.bottomSheetTopRadius),
          topRight: Radius.circular(UiUtils.bottomSheetTopRadius),
        ),
      ),
      padding: EdgeInsets.only(
        top: UiUtils.bottomSheetHorizontalContentPadding,
      ),
      constraints: BoxConstraints(
        maxHeight: MediaQuery.sizeOf(context).height * 0.875,
      ),
      child: Stack(
        children: [
          SingleChildScrollView(
            padding: EdgeInsets.only(
              bottom: UiUtils.bottomSheetHorizontalContentPadding,
              right: UiUtils.bottomSheetHorizontalContentPadding,
              left: UiUtils.bottomSheetHorizontalContentPadding,
              top:
                  MediaQuery.sizeOf(context).height * 0.05 +
                  UiUtils.bottomSheetHorizontalContentPadding * 0.5,
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: studyMaterials
                  .map(
                    (studyMaterial) => Center(
                      child: fromAnnouncementsContainer
                          ? AnnouncementAttachmentContainer(
                              studyMaterial: studyMaterial,
                              showDeleteButton: false,
                            )
                          : StudyMaterialContainer(
                              studyMaterial: studyMaterial,
                              showEditAndDeleteButton: false,
                            ),
                    ),
                  )
                  .toList(),
            ),
          ),
          Container(
            width: MediaQuery.sizeOf(context).width,
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.surface,
              border: Border(
                bottom: BorderSide(
                  color: Theme.of(
                    context,
                  ).colorScheme.onSurfaceVariant.withValues(alpha: 0.5),
                ),
              ),
            ),
            padding: EdgeInsets.only(
              right: UiUtils.bottomSheetHorizontalContentPadding,
              bottom: UiUtils.bottomSheetHorizontalContentPadding * 0.5,
              left: UiUtils.bottomSheetHorizontalContentPadding,
            ),
            child: Text(
              UiUtils.getTranslatedLabel(context, attachmentsKey),
              textAlign: TextAlign.start,
              style: TextStyle(
                fontSize: 16,
                color: Theme.of(context).colorScheme.onSurface,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
