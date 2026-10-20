import 'package:eschool_teacher/cubits/updateStudentLeaveStatusCubit.dart';
import 'package:eschool_teacher/data/models/studentLeave.dart';
import 'package:eschool_teacher/ui/widgets/bottomSheetTextFiledContainer.dart';
import 'package:eschool_teacher/ui/widgets/bottomSheetTopBarMenu.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class StudentLeaveDetailsBottomsheetContainer extends StatefulWidget {
  const StudentLeaveDetailsBottomsheetContainer({
    required this.leave, super.key,
  });
  final StudentLeave leave;

  @override
  State<StudentLeaveDetailsBottomsheetContainer> createState() =>
      StudentLeaveDetailsBottomsheetContainerState();
}

class StudentLeaveDetailsBottomsheetContainerState
    extends State<StudentLeaveDetailsBottomsheetContainer> {
  late StudentLeave leave;
  late Map<int, String> step;
  String reject = 'reject';
  String accept = 'accept';
  bool isLeaveAccepted = true;

  late final TextEditingController noteController = TextEditingController();

  Widget _buildLeaveTimingContainer(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(8),
        color: Theme.of(context).colorScheme.secondary.withValues(alpha: 0.06),
      ),
      padding: const EdgeInsets.all(15),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                UiUtils.getTranslatedLabel(context, leaveDateKey),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: Theme.of(context).colorScheme.secondary,
                  fontWeight: FontWeight.w600,
                ),
              ),
              Text(
                UiUtils.getTranslatedLabel(context, leaveTimingKey),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: Theme.of(context).colorScheme.secondary,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Divider(
            height: 10,
            color: Theme.of(
              context,
            ).colorScheme.onSurface.withValues(alpha: 0.1),
          ),
          ...List.generate(
            leave.leaveDetail!.length,
            (index) => Padding(
              padding: const EdgeInsets.only(top: 10),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    UiUtils.formatStringDate(leave.leaveDetail![index].date),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: Theme.of(
                        context,
                      ).colorScheme.secondary.withValues(alpha: 0.7),
                      fontSize: 16,
                    ),
                  ),
                  Text(
                    leave.leaveDetail![index].type,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: Theme.of(
                        context,
                      ).colorScheme.secondary.withValues(alpha: 0.7),
                      fontSize: 16,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  @override
  void initState() {
    step = {1: accept};
    leave = widget.leave;
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Theme.of(context).scaffoldBackgroundColor,
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(UiUtils.bottomSheetTopRadius),
          topRight: Radius.circular(UiUtils.bottomSheetTopRadius),
        ),
      ),
      margin: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      constraints: BoxConstraints(
        maxHeight: MediaQuery.sizeOf(context).height * 0.7,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        mainAxisSize: MainAxisSize.min,
        children: [
          if (!step.containsKey(1))
            const SizedBox.shrink()
          else
            BottomSheetTopBarMenu(
              onTapCloseButton: () {
                Navigator.pop(context);
              },
              padding: EdgeInsets.only(
                top: UiUtils.bottomSheetHorizontalContentPadding,
                left: UiUtils.bottomSheetHorizontalContentPadding,
                right: UiUtils.bottomSheetHorizontalContentPadding,
              ),
              title: UiUtils.getTranslatedLabel(context, leaveDetailsKey),
            ),
          Flexible(
            child: SingleChildScrollView(
              padding: EdgeInsets.symmetric(
                horizontal: UiUtils.bottomSheetHorizontalContentPadding,
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  if (!step.containsKey(1))
                    showStatusMessageAndAddNote(
                      context,
                      (step.containsValue(reject))
                          ? rejectedSuccessKey
                          : acceptedSuccessMsgKey,
                    )
                  else
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        const SizedBox(height: 15),
                        Text(
                          UiUtils.getTranslatedLabel(context, leaveReasonKey),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: Theme.of(context).colorScheme.secondary,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        const SizedBox(height: 5),
                        Text(
                          leave.reason,
                          style: TextStyle(
                            color: Theme.of(
                              context,
                            ).colorScheme.secondary.withValues(alpha: 0.76),
                            fontSize: 16,
                          ),
                        ),
                        const SizedBox(height: 20),
                        _buildLeaveTimingContainer(context),
                        const SizedBox(height: 25),
                        if (leave.file.isNotEmpty) ...[
                          Text(
                            UiUtils.getTranslatedLabel(context, attachmentsKey),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              color: Theme.of(context).colorScheme.secondary,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                          const SizedBox(height: 5),
                          Divider(
                            height: 20,
                            color: Theme.of(
                              context,
                            ).colorScheme.onSurface.withValues(alpha: 0.6),
                          ),
                          ...List.generate(
                            leave.file.length,
                            (index) => InkWell(
                              onTap: () {
                                UiUtils.viewOrDownloadStudyMaterial(
                                  context: context,
                                  studyMaterial: leave.file[index],
                                  storeInExternalStorage: false,
                                );
                              },
                              child: Padding(
                                padding: const EdgeInsets.symmetric(
                                  vertical: 10,
                                ),
                                child: Row(
                                  children: [
                                    Expanded(
                                      child: Text(
                                        leave.file[index].fileName,
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                        style: TextStyle(
                                          color: Theme.of(
                                            context,
                                          ).colorScheme.secondary,
                                          fontWeight: FontWeight.w600,
                                          fontSize: 16,
                                        ),
                                      ),
                                    ),
                                    Icon(
                                      Icons.remove_red_eye_outlined,
                                      color: Theme.of(
                                        context,
                                      ).colorScheme.secondary,
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(height: 20),
                        ],
                      ],
                    ),
                  if (leave.status ==
                      '0') //show Accept / Reject buttons only if status is PENDING
                    Container(
                      padding: const EdgeInsets.symmetric(vertical: 10),
                      child: Row(
                        children: [
                          //reject button
                          statusSelectionButton(
                            context,
                            false,
                            (step.containsKey(1))
                                ? rejectKey
                                : ((step.containsKey(2)) ? backKey : cancelKey),
                          ),
                          const SizedBox(width: 10),
                          //acceptButton
                          statusSelectionButton(
                            context,
                            true,
                            (step.containsKey(1))
                                ? acceptKey
                                : ((step.containsKey(2))
                                      ? confirmKey
                                      : submitKey),
                          ),
                        ],
                      ),
                    ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget showStatusMessageAndAddNote(
    BuildContext context,
    String messageTextKey,
  ) {
    return Container(
      margin: const EdgeInsets.only(top: 20),
      padding: const EdgeInsets.symmetric(vertical: 20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            UiUtils.getTranslatedLabel(context, messageTextKey),
            maxLines: 3,
            textAlign: TextAlign.center,
            style: TextStyle(
              color: Theme.of(context).colorScheme.secondary,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 15),
          Text(
            UiUtils.getTranslatedLabel(context, addNoteKey),
            textAlign: TextAlign.left,
            style: TextStyle(
              color: Theme.of(context).colorScheme.secondary,
              fontWeight: FontWeight.w400,
            ),
          ),
          const SizedBox(height: 10),
          BottomSheetTextFieldContainer(
            hintText: UiUtils.getTranslatedLabel(context, addNoteKey),
            margin: const EdgeInsets.only(bottom: 20),
            maxLines: 3,
            textEditingController: noteController,
          ),
        ],
      ),
    );
  }

  Widget showConfirmationMessage(BuildContext context, String messageTextKey) {
    return Container(
      margin: const EdgeInsets.only(top: 20),
      height: MediaQuery.sizeOf(context).height * 0.20,
      padding: const EdgeInsets.symmetric(vertical: 20),
      child: Text(
        UiUtils.getTranslatedLabel(context, messageTextKey),
        maxLines: 3,
        textAlign: TextAlign.center,
        style: TextStyle(
          color: Theme.of(context).colorScheme.secondary,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }

  Widget statusSelectionButton(
    BuildContext context,
    bool isAcceptButton,
    String captionKey,
  ) {
    return Flexible(
      child: InkWell(
        //Status -> 0-pending , 1-approved , 2-rejected
        onTap: () {
          if (step.containsKey(1)) {
            setState(() {
              step = isAcceptButton ? {2: accept} : {2: reject};
              isLeaveAccepted = isAcceptButton ? true : false;
            });
          } else if (step.containsKey(2)) {
            if (!isAcceptButton) {
              Navigator.of(context).pop();
            } else {
              if (context.read<UpdateStudentLeaveStatusCubit>().state
                  is UpdateStudentLeaveStatusInProgress) {
                return;
              }
              final String? note = (noteController.text.trim().isNotEmpty)
                  ? noteController.text.trim()
                  : null;
              context
                  .read<UpdateStudentLeaveStatusCubit>()
                  .updateStudentLeaveStatus(
                    leaveId: leave.id,
                    status: isLeaveAccepted ? '1' : '2',
                    reason: note,
                  )
                  .then((e) {
                    if (context.mounted) {
                      Navigator.pop(context);
                    }
                  });
            }
          }
        },
        child: Container(
          height: 40,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            border: !isAcceptButton
                ? Border.all(color: Theme.of(context).colorScheme.primary)
                : null,
            borderRadius: BorderRadius.circular(8),
            color: isAcceptButton
                ? Theme.of(context).colorScheme.primary
                : Theme.of(context).colorScheme.primary.withValues(alpha: 0.15),
          ),
          padding: const EdgeInsets.symmetric(vertical: 4, horizontal: 8),
          child: Text(
            UiUtils.getTranslatedLabel(context, captionKey),
            style: TextStyle(
              color: isAcceptButton
                  ? Theme.of(context).scaffoldBackgroundColor
                  : Theme.of(context).colorScheme.primary,
            ),
          ),
        ),
      ),
    );
  }
}
