import 'package:dotted_border/dotted_border.dart';
import 'package:eschool/cubits/uploadAssignmentCubit.dart';
import 'package:eschool/data/models/assignment.dart';
import 'package:eschool/ui/widgets/bottomsheetTopTitleAndCloseButton.dart';
import 'package:eschool/ui/widgets/customTextFieldContainer.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:permission_handler/permission_handler.dart';

class UploadAssignmentBottomsheetContainer extends StatefulWidget {
  const UploadAssignmentBottomsheetContainer({
    required this.assignment,
    super.key,
  });
  final Assignment assignment;

  @override
  State<UploadAssignmentBottomsheetContainer> createState() =>
      _UploadAssignmentBottomsheetContainerState();
}

class _UploadAssignmentBottomsheetContainerState
    extends State<UploadAssignmentBottomsheetContainer> {
  //for add any textual details with the submission
  TextEditingController textSubmissionTextFieldController =
      TextEditingController();
  //submitted files
  List<PlatformFile> uploadedFiles = [];

  @override
  void dispose() {
    textSubmissionTextFieldController.dispose();
    super.dispose();
  }

  Future<void> _pickFiles() async {
    final FilePickerResult? result = await FilePicker.pickFiles(
      allowMultiple: true,
    );
    if (result != null) {
      uploadedFiles.addAll(result.files);
      setState(() {});
    }
  }

  Future<void> _addFiles() async {
    if (UiUtils.showMobileOnlyNotice(context)) {
      return;
    }
    //upload files
    final permission = await Permission.storage.request();
    if (permission.isGranted) {
      await _pickFiles();
    } else {
      try {
        await _pickFiles();
      } on Exception {
        if (context.mounted) {
          UiUtils.showCustomSnackBar(
            context: context,
            errorMessage: UiUtils.getTranslatedLabel(
              context,
              allowStoragePermissionToContinueKey,
            ),
            backgroundColor: Theme.of(context).colorScheme.error,
          );
          await Future.delayed(const Duration(seconds: 2));
          openAppSettings();
        }
      }
    }
  }

  Widget _buildUploadedFileContainer(int fileIndex) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(15),
        color: Theme.of(context).colorScheme.surfaceContainerLow,
      ),
      padding: const EdgeInsets.symmetric(horizontal: 15, vertical: 10),
      child: LayoutBuilder(
        builder: (context, boxConstraints) {
          return Row(
            children: [
              SizedBox(
                width: boxConstraints.maxWidth * 0.75,
                child: Text(
                  uploadedFiles[fileIndex].name,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: Theme.of(context).colorScheme.onSurface,
                  ),
                ),
              ),
              const Spacer(),
              IconButton(
                onPressed: () {
                  if (context.read<UploadAssignmentCubit>().state
                      is UploadAssignmentInProgress) {
                    return;
                  }
                  uploadedFiles.removeAt(fileIndex);
                  setState(() {});
                },
                icon: const Icon(Icons.close),
              ),
            ],
          );
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      onPopInvokedWithResult: (didPop, dynamic result) {
        if (context.read<UploadAssignmentCubit>().state
            is UploadAssignmentInProgress) {
          context.read<UploadAssignmentCubit>().cancelUploadAssignmentProcess();
        }
      },
      child: SingleChildScrollView(
        child: Container(
          padding: EdgeInsets.symmetric(
            horizontal: MediaQuery.sizeOf(context).width * 0.075,
            vertical: MediaQuery.sizeOf(context).height * 0.04,
          ),
          margin: EdgeInsets.only(
            bottom: MediaQuery.of(context).viewInsets.bottom,
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              BottomsheetTopTitleAndCloseButton(
                onTapCloseButton: () {
                  if (context.read<UploadAssignmentCubit>().state
                      is UploadAssignmentInProgress) {
                    context
                        .read<UploadAssignmentCubit>()
                        .cancelUploadAssignmentProcess();
                  }
                  Navigator.of(context).pop();
                },
                titleKey: submitAssignmentKey,
              ),
              Text(
                UiUtils.getTranslatedLabel(
                  context,
                  assignmentSubmissionDisclaimerKey,
                ),
                style: const TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 15),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 5),
                child: CustomTextFieldContainer(
                  hideText: true,
                  hintTextKey: textSubmissionKey,
                  bottomPadding: 20,
                  textEditingController: textSubmissionTextFieldController,
                  maxLines: 5,
                  borderRadius: 15,
                  textAlign: TextAlign.center,
                  maxLength: 2000,
                ),
              ),
              InkWell(
                borderRadius: BorderRadius.circular(15),
                onTap: () async {
                  _addFiles();
                },
                child: DottedBorder(
                  options: RoundedRectDottedBorderOptions(
                    dashPattern: const [10, 10],
                    radius: const Radius.circular(15),
                    color: Theme.of(
                      context,
                    ).colorScheme.onSurfaceVariant.withValues(alpha: 0.3),
                  ),
                  child: Container(
                    alignment: Alignment.center,
                    width: MediaQuery.sizeOf(context).width * 0.8,
                    height: MediaQuery.sizeOf(context).height * 0.05,
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Container(
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            color: Theme.of(context).colorScheme.primary,
                            boxShadow: [
                              BoxShadow(
                                blurRadius: 15,
                                offset: const Offset(0, 1.5),
                                color: Theme.of(
                                  context,
                                ).colorScheme.primary.withValues(alpha: 0.3),
                              ),
                            ],
                          ),
                          width: 25,
                          height: 25,
                          child: Icon(
                            Icons.add,
                            size: 15,
                            color: Theme.of(context).colorScheme.onPrimary,
                          ),
                        ),
                        SizedBox(
                          width: MediaQuery.sizeOf(context).width * 0.05,
                        ),
                        Text(
                          UiUtils.getTranslatedLabel(context, addFilesKey),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: Theme.of(context).colorScheme.onSurface,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
              SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
              ...List.generate(
                uploadedFiles.length,
                (index) => index,
              ).map((fileIndex) => _buildUploadedFileContainer(fileIndex)),
              uploadedFiles.isEmpty
                  ? const SizedBox()
                  : SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
              BlocConsumer<UploadAssignmentCubit, UploadAssignmentState>(
                listener: (context, state) {
                  if (state is UploadAssignmentFetchSuccess) {
                    Navigator.of(context).pop({
                      'error': false,
                      'assignmentSubmission': state.assignmentSubmission,
                    });
                  } else if (state is UploadAssignmentFailure) {
                    Navigator.of(
                      context,
                    ).pop({'error': true, 'message': state.errorMessage});
                  }
                },
                builder: (context, state) {
                  return CustomRoundedButton(
                    onTap: () {
                      FocusScope.of(context).unfocus();
                      if (uploadedFiles.isEmpty &&
                          textSubmissionTextFieldController.text
                              .trim()
                              .isEmpty) {
                        UiUtils.showCustomSnackBar(
                          context: context,
                          errorMessage: UiUtils.getTranslatedLabel(
                            context,
                            pleaseAddAtleastOneFileOrTextToSubmitKey,
                          ),
                          backgroundColor: Theme.of(context).colorScheme.error,
                        );
                        return;
                      }
                      if (state is UploadAssignmentInProgress) {
                        return;
                      }
                      context.read<UploadAssignmentCubit>().uploadAssignment(
                        assignmentSubmissionId:
                            widget.assignment.assignmentSubmission.id,
                        assignmentId: widget.assignment.id,
                        filePaths: uploadedFiles
                            .map((file) => file.path!)
                            .toList(),
                        context: context,
                        textSubmission: textSubmissionTextFieldController.text,
                      );
                    },
                    height: 40,
                    widthPercentage: state is UploadAssignmentInProgress
                        ? 0.75
                        : 0.35,
                    backgroundColor: Theme.of(context).colorScheme.primary,
                    buttonTitle: state is UploadAssignmentInProgress
                        ? '${UiUtils.getTranslatedLabel(context, submittingKey)} (${state.uploadedProgress.toStringAsFixed(2)})%'
                        : UiUtils.getTranslatedLabel(context, submitKey),
                    showBorder: false,
                  );
                },
              ),
            ],
          ),
        ),
      ),
    );
  }
}
