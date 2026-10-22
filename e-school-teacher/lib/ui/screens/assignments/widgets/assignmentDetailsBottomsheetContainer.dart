import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/data/models/assignment.dart';
import 'package:eschool_teacher/ui/widgets/announcementAttachmentContainer.dart';
import 'package:eschool_teacher/ui/widgets/customSwitch.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
// ignore: depend_on_referenced_packages
import 'package:intl/intl.dart';

class AssignmentDetailsBottomsheetContainer extends StatefulWidget {
  const AssignmentDetailsBottomsheetContainer({
    required this.assignment,
    super.key,
  });
  final Assignment assignment;

  @override
  State<AssignmentDetailsBottomsheetContainer> createState() =>
      _AssignmentDetailsBottomsheetContainerState();
}

class _AssignmentDetailsBottomsheetContainerState
    extends State<AssignmentDetailsBottomsheetContainer> {
  TextStyle _getAssignmentDetailsLabelValueTextStyle() {
    return TextStyle(
      color: Theme.of(context).colorScheme.onSurface,
      fontSize: 14,
      fontWeight: FontWeight.w600,
    );
  }

  TextStyle _getAssignmentDetailsLabelTextStyle() {
    return TextStyle(
      color: Theme.of(context).colorScheme.onSurfaceVariant,
      fontSize: 12,
      fontWeight: FontWeight.w400,
    );
  }

  Widget _buildAssignmentDetailBackgroundContainer(Widget child) {
    return Center(
      child: Container(
        margin: const EdgeInsets.only(bottom: 20),
        width: MediaQuery.sizeOf(context).width,
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surfaceContainerLow,
          borderRadius: BorderRadius.circular(10),
        ),
        child: child,
      ),
    );
  }

  Widget _buildAssignmentSubjectNameContainer() {
    return _buildAssignmentDetailBackgroundContainer(
      Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            UiUtils.getTranslatedLabel(context, subjectNameKey),
            style: _getAssignmentDetailsLabelTextStyle(),
          ),
          const SizedBox(height: 5),
          Text(
            widget.assignment.subject.subjectNameWithTypeAndSemester,
            style: _getAssignmentDetailsLabelValueTextStyle(),
          ),
        ],
      ),
    );
  }

  Widget _buildAssignmentAssignedDateContainer() {
    //yyyy.MMMMM.dd GGG hh:mm aaa
    final DateTime now = DateTime.parse(widget.assignment.createdAt);
    final DateFormat formatter = DateFormat('dd MMM yyyy, hh:mm aaa');
    final String formatted = formatter.format(now);
    return _buildAssignmentDetailBackgroundContainer(
      Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            UiUtils.getTranslatedLabel(context, assignedDateKey),
            style: _getAssignmentDetailsLabelTextStyle(),
          ),
          const SizedBox(height: 5),
          Text(
            'Due, $formatted',
            style: _getAssignmentDetailsLabelValueTextStyle(),
          ),
        ],
      ),
    );
  }

  Widget _buildAssignmentDueDateContainer() {
    final DateTime now = widget.assignment.dueDate;
    final DateFormat formatter = DateFormat('dd MMM yyyy, hh:mm aaa');
    final String formatted = formatter.format(now);
    return _buildAssignmentDetailBackgroundContainer(
      Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            UiUtils.getTranslatedLabel(context, dueDateKey),
            style: _getAssignmentDetailsLabelTextStyle(),
          ),
          const SizedBox(height: 5),
          Text(
            'Due, $formatted',
            style: _getAssignmentDetailsLabelValueTextStyle(),
          ),
        ],
      ),
    );
  }

  Widget _buildAssignmentPointsContainer() {
    return _buildAssignmentDetailBackgroundContainer(
      Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            UiUtils.getTranslatedLabel(context, pointsKey),
            style: _getAssignmentDetailsLabelTextStyle(),
          ),
          const SizedBox(height: 5),
          Text(
            widget.assignment.points.toString(),
            style: _getAssignmentDetailsLabelValueTextStyle(),
          ),
        ],
      ),
    );
  }

  Widget _buildAssignmentInstructionsContainer() {
    return _buildAssignmentDetailBackgroundContainer(
      Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            UiUtils.getTranslatedLabel(context, instructionsKey),
            style: _getAssignmentDetailsLabelTextStyle(),
          ),
          const SizedBox(height: 5),
          Text(
            widget.assignment.instructions,
            style: _getAssignmentDetailsLabelValueTextStyle(),
          ),
        ],
      ),
    );
  }

  Widget _buildAssignmentReferenceMaterialsContainer() {
    return _buildAssignmentDetailBackgroundContainer(
      LayoutBuilder(
        builder: (context, boxConstraints) {
          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                UiUtils.getTranslatedLabel(context, referenceMaterialsKey),
                style: _getAssignmentDetailsLabelTextStyle(),
              ),
              const SizedBox(height: 5),
              ...List.generate(
                widget.assignment.studyMaterial.length,
                (index) => Transform.translate(
                  offset: const Offset(-15, 0),
                  child: AnnouncementAttachmentContainer(
                    backgroundColor: Colors.transparent,
                    showDeleteButton: false,
                    studyMaterial: widget.assignment.studyMaterial[index],
                  ),
                ),
              ),
              const SizedBox(height: 15),
            ],
          );
        },
      ),
    );
  }

  //Add
  Widget _buildReSubmissionOfRejectedASsignmentToggleContainer() {
    final bool isResubmission = widget.assignment.resubmission != 0;
    return _buildAssignmentDetailBackgroundContainer(
      LayoutBuilder(
        builder: (context, boxConstraints) {
          return Row(
            children: [
              Flexible(
                child: SizedBox(
                  width: boxConstraints.maxWidth * 0.825,
                  child: Text(
                    UiUtils.getTranslatedLabel(
                      context,
                      resubmissionOfRejectedAssignmentKey,
                    ),
                    style: _getAssignmentDetailsLabelValueTextStyle(),
                  ),
                ),
              ),
              SizedBox(width: boxConstraints.maxWidth * 0.075),
              Container(
                alignment: Alignment.centerRight,
                width: boxConstraints.maxWidth * 0.1,
                child: SizedBox(
                  width: 30,
                  child: CustomSwitch(onChanged: (_) {}, value: isResubmission),
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _buildExtraDayForRejectedAssignmentContainer() {
    return _buildAssignmentDetailBackgroundContainer(
      Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            UiUtils.getTranslatedLabel(
              context,
              extraDaysForRejectedAssignmentKey,
            ),
            style: _getAssignmentDetailsLabelTextStyle(),
          ),
          const SizedBox(height: 5),
          Text(
            widget.assignment.extraDaysForResubmission.toString(),
            style: _getAssignmentDetailsLabelValueTextStyle(),
          ),
        ],
      ),
    );
  }

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
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          //
          Container(
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
            padding: EdgeInsets.symmetric(
              horizontal: UiUtils.bottomSheetHorizontalContentPadding,
            ),
            child: LayoutBuilder(
              builder: (context, boxConstraints) {
                return Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Text(
                        widget.assignment.name,
                        textAlign: TextAlign.start,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          fontSize: 16,
                          color: Theme.of(context).colorScheme.onSurface,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                    TextButton(
                      onPressed: () {
                        Navigator.of(context).pushNamed(
                          Routes.assignment,
                          arguments: widget.assignment,
                        );
                      },
                      child: Text(
                        UiUtils.getTranslatedLabel(context, viewKey),
                        style: TextStyle(
                          color: Theme.of(context).colorScheme.primary,
                        ),
                      ),
                    ),
                  ],
                );
              },
            ),
          ),

          Expanded(
            child: ListView(
              padding: EdgeInsets.symmetric(
                horizontal: UiUtils.bottomSheetHorizontalContentPadding,
              ),
              children: [
                SizedBox(height: UiUtils.bottomSheetHorizontalContentPadding),
                _buildAssignmentSubjectNameContainer(),
                _buildAssignmentAssignedDateContainer(),
                _buildAssignmentDueDateContainer(),
                if (widget.assignment.instructions.isNotEmpty)
                  _buildAssignmentInstructionsContainer(),
                if (widget.assignment.studyMaterial.isNotEmpty &&
                    widget.assignment.studyMaterial != [])
                  _buildAssignmentReferenceMaterialsContainer(),

                _buildAssignmentPointsContainer(),

                // _buildLateSubmissionToggleContainer(),
                _buildReSubmissionOfRejectedASsignmentToggleContainer(),
                if (widget.assignment.resubmission == 1)
                  _buildExtraDayForRejectedAssignmentContainer(),
                //_buildDeleteAndEditButtonContainer(),
                SizedBox(height: UiUtils.bottomSheetHorizontalContentPadding),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
