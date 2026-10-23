import 'package:dotted_border/dotted_border.dart';
import 'package:eschool_teacher/cubits/updateStudyMaterialCubit.dart';
import 'package:eschool_teacher/data/models/pickedStudyMaterial.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/ui/widgets/defaultDropDownLabelContainer.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:permission_handler/permission_handler.dart';

class EditStudyMaterialBottomSheet extends StatefulWidget {
  const EditStudyMaterialBottomSheet({required this.studyMaterial, super.key});
  final StudyMaterial studyMaterial;

  @override
  State<EditStudyMaterialBottomSheet> createState() =>
      _EditStudyMaterialBottomSheetState();
}

class _EditStudyMaterialBottomSheetState
    extends State<EditStudyMaterialBottomSheet> {
  late final TextEditingController _studyMaterialNameEditingController =
      TextEditingController(text: widget.studyMaterial.fileName);

  late final TextEditingController _youtubeLinkEditingController =
      TextEditingController(
        text:
            widget.studyMaterial.studyMaterialType ==
                StudyMaterialType.youtubeVideo
            ? widget.studyMaterial.fileUrl
            : null,
      );

  PlatformFile? addedFile; //if studymaterial type is fileUpload
  PlatformFile?
  addedVideoThumbnailFile; //if studymaterial type is not fileUpload
  PlatformFile? addedVideoFile; //if studymaterial type is videoUpload

  void showErrorMessage(String messageKey) {
    UiUtils.showBottomToastOverlay(
      context: context,
      errorMessage: UiUtils.getTranslatedLabel(context, messageKey),
      backgroundColor: Theme.of(context).colorScheme.error,
    );
  }

  Future<void> editStudyMaterial() async {
    FocusManager.instance.primaryFocus?.unfocus();
    if (_studyMaterialNameEditingController.text.trim().isEmpty) {
      showErrorMessage(pleaseEnterStudyMaterialNameKey);
      return;
    }

    if (widget.studyMaterial.studyMaterialType ==
            StudyMaterialType.youtubeVideo &&
        (_youtubeLinkEditingController.text.trim().isEmpty ||
            !Uri.parse(_youtubeLinkEditingController.text.trim()).isAbsolute)) {
      showErrorMessage(pleaseEnterYoutubeLinkKey);
      return;
    }

    final pickedStudyMaterialTypeId = UiUtils.getStudyMaterialIdByEnum(
      widget.studyMaterial.studyMaterialType,
      context,
    );

    final pickedStudyMaterial = PickedStudyMaterial(
      fileName: _studyMaterialNameEditingController.text.trim(),
      pickedStudyMaterialTypeId: pickedStudyMaterialTypeId,
      studyMaterialFile: pickedStudyMaterialTypeId == 1
          ? addedFile
          : addedVideoFile,
      videoThumbnailFile: addedVideoThumbnailFile,
      youTubeLink: _youtubeLinkEditingController.text.trim(),
    );

    context.read<UpdateStudyMaterialCubit>().updateStudyMaterial(
      fileId: widget.studyMaterial.id,
      pickedStudyMaterial: pickedStudyMaterial,
    );
  }

  Widget _buildAddedFileContainer(PlatformFile file, Function onTap) {
    return LayoutBuilder(
      builder: (context, boxConstraints) {
        return DottedBorder(
          options: RoundedRectDottedBorderOptions(
            dashPattern: const [10, 10],
            radius: const Radius.circular(10),
            color: Theme.of(context).colorScheme.onSurfaceVariant
                .withValues(alpha: 0.3),
          ),
          child: Padding(
            padding: const EdgeInsetsDirectional.only(start: 10),
            child: Row(
              children: [
                SizedBox(
                  width: boxConstraints.maxWidth * 0.75,
                  child: Text(
                    file.name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                const Spacer(),
                IconButton(
                  onPressed: () {
                    onTap();
                  },
                  icon: const Icon(Icons.close),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: MediaQuery.of(context).viewInsets,
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            BottomSheetTopBarMenu(
              onTapCloseButton: () {
                Navigator.of(context).pop();
              },
              title: UiUtils.getTranslatedLabel(context, editStudyMaterialKey),
            ),
            Padding(
              padding: EdgeInsets.symmetric(
                horizontal: UiUtils.bottomSheetHorizontalContentPadding,
              ),
              child: Column(
                children: [
                  Transform.translate(
                    offset: const Offset(0, -15),
                    child: Row(
                      children: [
                        CircleAvatar(
                          radius: 2.5,
                          backgroundColor: Theme.of(context)
                              .colorScheme
                              .onSurfaceVariant,
                        ),
                        const SizedBox(width: 10),
                        Text(
                          UiUtils.getTranslatedLabel(
                            context,
                            oldFilesWillBeReplacedWithLatestOneKey,
                          ),
                          style: const TextStyle(fontSize: 13),
                        ),
                      ],
                    ),
                  ),
                  LayoutBuilder(
                    builder: (context, boxConstraints) {
                      //Study material type dropdown list
                      return DefaultDropDownLabelContainer(
                        titleLabelKey: UiUtils.getStudyMaterialTypeLabelByEnum(
                          widget.studyMaterial.studyMaterialType,
                          context,
                        ),
                        width: boxConstraints.maxWidth,
                      );
                    },
                  ),
                  BottomSheetTextFieldContainer(
                    margin: const EdgeInsets.only(bottom: 25),
                    hintText: UiUtils.getTranslatedLabel(
                      context,
                      studyMaterialNameKey,
                    ),
                    maxLines: 1,
                    textEditingController: _studyMaterialNameEditingController,
                  ),

                  //
                  //if file or images has been picked then show the pickedFile name and remove button
                  //else show file picker option
                  //
                  Padding(
                    padding: const EdgeInsets.only(bottom: 25),
                    child: addedFile != null
                        ? _buildAddedFileContainer(addedFile!, () {
                            addedFile = null;
                            setState(() {});
                          })
                        : addedVideoThumbnailFile != null
                        ? _buildAddedFileContainer(
                            addedVideoThumbnailFile!,
                            () {
                              addedVideoThumbnailFile = null;
                              setState(() {});
                            },
                          )
                        : BottomsheetAddFilesDottedBorderContainer(
                            onTap: () async {
                              if (UiUtils.showMobileOnlyNotice(context)) {
                                return;
                              }
                              try {
                                final pickedFile = await FilePicker.pickFile(
                                  type:
                                      widget.studyMaterial.studyMaterialType ==
                                          StudyMaterialType.file
                                      ? FileType.any
                                      : FileType.image,
                                );
                                //
                                //
                                if (pickedFile != null) {
                                  //if current selected study material type is file
                                  if (widget.studyMaterial.studyMaterialType ==
                                      StudyMaterialType.file) {
                                    addedFile = pickedFile;
                                  } else {
                                    addedVideoThumbnailFile = pickedFile;
                                  }

                                  setState(() {});
                                }
                              } on Error {
                                showErrorMessage(permissionToPickFileKey);
                                await Future.delayed(
                                  const Duration(seconds: 2),
                                );
                                openAppSettings();
                              }
                            },
                            title:
                                widget.studyMaterial.studyMaterialType ==
                                    StudyMaterialType.file
                                ? UiUtils.getTranslatedLabel(
                                    context,
                                    selectFileKey,
                                  )
                                : UiUtils.getTranslatedLabel(
                                    context,
                                    selectThumbnailKey,
                                  ),
                          ),
                  ),

                  if (widget.studyMaterial.studyMaterialType ==
                      StudyMaterialType.youtubeVideo)
                    BottomSheetTextFieldContainer(
                      margin: const EdgeInsets.only(bottom: 25),
                      hintText: UiUtils.getTranslatedLabel(
                        context,
                        youtubeLinkKey,
                      ),
                      maxLines: 2,
                      textEditingController: _youtubeLinkEditingController,
                    )
                  else
                    widget.studyMaterial.studyMaterialType ==
                            StudyMaterialType.uploadedVideoUrl
                        ? addedVideoFile != null
                              ? _buildAddedFileContainer(addedVideoFile!, () {
                                  addedVideoFile = null;
                                  setState(() {});
                                })
                              : Padding(
                                  padding: const EdgeInsets.only(bottom: 25),
                                  child:
                                      BottomsheetAddFilesDottedBorderContainer(
                                        onTap: () async {
                                          if (UiUtils.showMobileOnlyNotice(
                                            context,
                                          )) {
                                            return;
                                          }
                                          try {
                                            final pickedFile =
                                                await FilePicker.pickFile(
                                                  type: FileType.video,
                                                );

                                            if (pickedFile != null) {
                                              addedVideoFile = pickedFile;
                                              setState(() {});
                                            }
                                          } on Exception {
                                            showErrorMessage(
                                              permissionToPickFileKey,
                                            );
                                            await Future.delayed(
                                              const Duration(seconds: 2),
                                            );
                                            openAppSettings();
                                          }
                                        },
                                        title:
                                            widget
                                                    .studyMaterial
                                                    .studyMaterialType ==
                                                StudyMaterialType.file
                                            ? UiUtils.getTranslatedLabel(
                                                context,
                                                selectFileKey,
                                              )
                                            : UiUtils.getTranslatedLabel(
                                                context,
                                                selectVideoKey,
                                              ),
                                      ),
                                )
                        : const SizedBox(),
                ],
              ),
            ),
            BlocConsumer<UpdateStudyMaterialCubit, UpdateStudyMaterialState>(
              listener: (context, state) {
                if (state is UpdateStudyMaterialSuccess) {
                  Navigator.of(context).pop(state.studyMaterial);
                } else if (state is UpdateStudyMaterialFailure) {
                  UiUtils.showBottomToastOverlay(
                    context: context,
                    errorMessage: UiUtils.getErrorMessageFromErrorCode(
                      context,
                      state.exception,
                    ),
                    backgroundColor: Theme.of(context).colorScheme.error,
                  );
                }
              },
              builder: (context, state) {
                return CustomRoundedButton(
                  onTap: () {
                    if (state is UpdateStudyMaterialInProgress) {
                      return;
                    }
                    editStudyMaterial();
                  },
                  height: UiUtils.bottomSheetButtonHeight,
                  widthPercentage: UiUtils.bottomSheetButtonWidthPercentage,
                  backgroundColor: Theme.of(context).colorScheme.primary,
                  buttonTitle: UiUtils.getTranslatedLabel(context, submitKey),
                  showBorder: false,
                  child: state is UpdateStudyMaterialInProgress
                      ? const CustomCircularProgressIndicator(
                          strokeWidth: 2,
                          widthAndHeight: 20,
                        )
                      : null,
                );
              },
            ),
            const SizedBox(height: 25),
          ],
        ),
      ),
    );
  }
}
