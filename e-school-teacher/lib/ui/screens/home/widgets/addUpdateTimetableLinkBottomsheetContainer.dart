// ignore_for_file: public_member_api_docs, sort_constructors_first
import 'package:eschool_teacher/cubits/dashboardCubit.dart';
import 'package:eschool_teacher/cubits/updateTimetableLinkCubit.dart';
import 'package:eschool_teacher/data/models/timeTableSlot.dart';
import 'package:eschool_teacher/ui/widgets/bottomSheetTextFiledContainer.dart';
import 'package:eschool_teacher/ui/widgets/bottomSheetTopBarMenu.dart';
import 'package:eschool_teacher/ui/widgets/customCircularProgressIndicator.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class AddUpdateTimetableLinkBottomsheetContaienr extends StatefulWidget {
  final TimeTableSlot timetableSlot;
  const AddUpdateTimetableLinkBottomsheetContaienr({
    required this.timetableSlot,
    super.key,
  });

  @override
  State<AddUpdateTimetableLinkBottomsheetContaienr> createState() =>
      _AddUpdateTimetableLinkBottomsheetContaienrState();
}

class _AddUpdateTimetableLinkBottomsheetContaienrState
    extends State<AddUpdateTimetableLinkBottomsheetContaienr> {
  late final TextEditingController _linkNameController = TextEditingController(
    text: widget.timetableSlot.linkName ?? '',
  );
  late final TextEditingController _linkCustomUrlController =
      TextEditingController(text: widget.timetableSlot.linkCustomUrl ?? '');

  @override
  void dispose() {
    _linkNameController.dispose();
    _linkCustomUrlController.dispose();
    super.dispose();
  }

  void _onUpdate() {
    FocusScope.of(context).unfocus();
    if (_linkNameController.text.trim().isEmpty ||
        _linkCustomUrlController.text.trim().isEmpty) {
      UiUtils.showBottomToastOverlay(
        context: context,
        errorMessage: UiUtils.getTranslatedLabel(context, fillBothFieldsKey),
        backgroundColor: Theme.of(context).colorScheme.error,
      );
    } else if (Uri.parse(_linkCustomUrlController.text.trim()).isAbsolute) {
      context.read<UpdateTimetableLinkCubit>().updateTimetableLink(
        timetableSlotId: widget.timetableSlot.id,
        url: _linkCustomUrlController.text.trim(),
        name: _linkNameController.text.trim(),
      );
    } else {
      UiUtils.showBottomToastOverlay(
        context: context,
        errorMessage: UiUtils.getTranslatedLabel(context, enterValidLinkKey),
        backgroundColor: Theme.of(context).colorScheme.error,
      );
    }
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
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      constraints: BoxConstraints(
        maxHeight: MediaQuery.sizeOf(context).height * 0.7,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        mainAxisSize: MainAxisSize.min,
        children: [
          BottomSheetTopBarMenu(
            onTapCloseButton: () {
              Navigator.pop(context);
            },
            padding: EdgeInsets.only(
              top: UiUtils.bottomSheetHorizontalContentPadding,
              left: UiUtils.bottomSheetHorizontalContentPadding,
              right: UiUtils.bottomSheetHorizontalContentPadding,
            ),
            title: UiUtils.getTranslatedLabel(
              context,
              widget.timetableSlot.hasCustomLink
                  ? editTimetableLinkKey
                  : addTimetableLinkKey,
            ),
          ),
          Flexible(
            child: SingleChildScrollView(
              padding: EdgeInsets.symmetric(
                horizontal: UiUtils.bottomSheetHorizontalContentPadding,
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const SizedBox(height: 15),
                  Text(
                    UiUtils.getTranslatedLabel(context, linkTitleKey),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.secondary,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  BottomSheetTextFieldContainer(
                    hintText: UiUtils.getTranslatedLabel(context, linkTitleKey),
                    margin: const EdgeInsets.only(bottom: 20, top: 8),
                    textEditingController: _linkNameController,
                    maxLines: null,
                  ),
                  Text(
                    UiUtils.getTranslatedLabel(context, liveClassLinkKey),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.secondary,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  BottomSheetTextFieldContainer(
                    hintText: UiUtils.getTranslatedLabel(
                      context,
                      liveClassLinkKey,
                    ),
                    margin: const EdgeInsets.only(bottom: 20, top: 8),
                    textEditingController: _linkCustomUrlController,
                    maxLines: null,
                  ),
                  Row(
                    children: [
                      Expanded(
                        child: CustomOutlinedFilledButton(
                          isFilled: false,
                          titleKey: clearKey,
                          onTap: () {
                            _linkNameController.clear();
                            _linkCustomUrlController.clear();
                          },
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child:
                            BlocConsumer<
                              UpdateTimetableLinkCubit,
                              UpdateTimetableLinkState
                            >(
                              listener: (context, state) {
                                if (state is UpdateTimetableLinkFailure) {
                                  UiUtils.showBottomToastOverlay(
                                    context: context,
                                    errorMessage:
                                        UiUtils.getErrorMessageFromErrorCode(
                                          context,
                                          state.exception,
                                        ),
                                    backgroundColor: Theme.of(
                                      context,
                                    ).colorScheme.error,
                                  );
                                } else if (state
                                    is UpdateTimetableLinkSuccess) {
                                  UiUtils.showBottomToastOverlay(
                                    context: context,
                                    errorMessage: UiUtils.getTranslatedLabel(
                                      context,
                                      timetableSlotLinkSuccessfullyUpdatedKey,
                                    ),
                                    backgroundColor: Theme.of(
                                      context,
                                    ).colorScheme.onPrimary,
                                  );
                                  widget.timetableSlot.linkName = state.name;
                                  widget.timetableSlot.linkCustomUrl =
                                      state.url;
                                  //also updating timetable item on dashboard (if it exists there)
                                  context
                                      .read<DashboardCubit>()
                                      .updateTimetableItem(
                                        widget.timetableSlot,
                                      );
                                  Navigator.of(context).pop(true);
                                }
                              },
                              builder:
                                  (
                                    BuildContext context,
                                    UpdateTimetableLinkState state,
                                  ) {
                                    return CustomOutlinedFilledButton(
                                      titleKey: submitKey,
                                      isLoading:
                                          state
                                              is UpdateTimetableLinkInProgress,
                                      onTap: () {
                                        if (state
                                            is UpdateTimetableLinkInProgress) {
                                          return;
                                        }
                                        _onUpdate();
                                      },
                                    );
                                  },
                            ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 20),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class CustomOutlinedFilledButton extends StatelessWidget {
  final String titleKey;
  final bool isFilled;
  final bool isLoading;
  final void Function()? onTap;
  const CustomOutlinedFilledButton({
    required this.titleKey,
    super.key,
    this.isFilled = true,
    this.isLoading = false,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(8),
        child: Container(
          height: 40,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            border: !isFilled
                ? Border.all(color: Theme.of(context).colorScheme.primary)
                : null,
            borderRadius: BorderRadius.circular(8),
            color: isFilled
                ? Theme.of(context).colorScheme.primary
                : Theme.of(context).colorScheme.primary.withValues(alpha: 0.15),
          ),
          padding: const EdgeInsets.symmetric(vertical: 4, horizontal: 8),
          child: isLoading
              ? const CustomCircularProgressIndicator(
                  strokeWidth: 2,
                  widthAndHeight: 20,
                )
              : Text(
                  UiUtils.getTranslatedLabel(context, titleKey),
                  textAlign: TextAlign.center,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: isFilled
                        ? Theme.of(context).scaffoldBackgroundColor
                        : Theme.of(context).colorScheme.primary,
                  ),
                ),
        ),
      ),
    );
  }
}
