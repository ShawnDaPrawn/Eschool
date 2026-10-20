import 'package:eschool/cubits/undoAssignmentSubmissionCubit.dart';
import 'package:eschool/ui/widgets/bottomsheetTopTitleAndCloseButton.dart';
import 'package:eschool/ui/widgets/customRoundedButton.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class UndoAssignmentBottomsheetContainer extends StatefulWidget {
  const UndoAssignmentBottomsheetContainer({
    required this.assignmentSubmissionId, super.key,
  });
  final int assignmentSubmissionId;

  @override
  State<UndoAssignmentBottomsheetContainer> createState() =>
      _UndoAssignmentBottomsheetContainerState();
}

class _UndoAssignmentBottomsheetContainerState
    extends State<UndoAssignmentBottomsheetContainer> {
  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, dynamic result) {
        if (context.read<UndoAssignmentSubmissionCubit>().state
            is! UndoAssignmentSubmissionInProgress) {
          if (didPop) {
            return;
          }
          Navigator.of(context).pop();
        }
      },
      child: Container(
        padding: EdgeInsets.symmetric(
          horizontal: MediaQuery.sizeOf(context).width * 0.075,
          vertical: MediaQuery.sizeOf(context).height * 0.04,
        ),
        decoration: BoxDecoration(
          color: Theme.of(context).scaffoldBackgroundColor,
          borderRadius: BorderRadius.only(
            topLeft: Radius.circular(UiUtils.bottomSheetTopRadius),
            topRight: Radius.circular(UiUtils.bottomSheetTopRadius),
          ),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            BottomsheetTopTitleAndCloseButton(
              onTapCloseButton: () {
                if (context.read<UndoAssignmentSubmissionCubit>().state
                    is UndoAssignmentSubmissionInProgress) {
                  return;
                }
                Navigator.of(context).pop();
              },
              titleKey: undoSubmissionKey,
            ),
            SizedBox(height: MediaQuery.sizeOf(context).height * 0.0125),
            Text(
              UiUtils.getTranslatedLabel(context, undoSubmissionWarningKey),
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 16,
                color: Theme.of(context).colorScheme.secondary,
              ),
            ),
            SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
            BlocConsumer<
              UndoAssignmentSubmissionCubit,
              UndoAssignmentSubmissionState
            >(
              listener: (context, state) {
                if (state is UndoAssignmentSubmissionFailure) {
                  Navigator.of(
                    context,
                  ).pop({'error': true, 'message': state.errorMessage});
                } else if (state is UndoAssignmentSubmissionSuccess) {
                  Navigator.of(context).pop({'error': false});
                }
              },
              builder: (context, state) {
                return CustomRoundedButton(
                  onTap: () {
                    if (state is UndoAssignmentSubmissionInProgress) {
                      return;
                    }
                    context
                        .read<UndoAssignmentSubmissionCubit>()
                        .undoAssignmentSubmission(
                          assignmentSubmissionId: widget.assignmentSubmissionId,
                        );
                  },
                  height: 40,
                  textSize: 16,
                  widthPercentage: 0.45,
                  titleColor: Theme.of(context).scaffoldBackgroundColor,
                  backgroundColor: Theme.of(context).colorScheme.primary,
                  buttonTitle: UiUtils.getTranslatedLabel(
                    context,
                    state is UndoAssignmentSubmissionInProgress
                        ? undoingKey
                        : undoKey,
                  ),
                  showBorder: false,
                );
              },
            ),
          ],
        ),
      ),
    );
  }
}
