import 'package:eschool_teacher/data/models/pickedStudyMaterial.dart';
import 'package:eschool_teacher/ui/widgets/addedFileContainer.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/ui/widgets/customDropDownMenu.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_keyboard_visibility_temp_fork/flutter_keyboard_visibility_temp_fork.dart';
import 'package:permission_handler/permission_handler.dart';

class AddStudyMaterialBottomsheet extends StatefulWidget {
  const AddStudyMaterialBottomsheet({
    required this.editFileDetails,
    required this.onTapSubmit,
    super.key,
    this.pickedStudyMaterial,
  });
  final Function(PickedStudyMaterial) onTapSubmit;
  final bool editFileDetails;
  final PickedStudyMaterial? pickedStudyMaterial;

  @override
  State<AddStudyMaterialBottomsheet> createState() =>
      _AddStudyMaterialBottomsheetState();
}

class _AddStudyMaterialBottomsheetState
    extends State<AddStudyMaterialBottomsheet> {
  late CustomDropDownItem currentSelectedStudyMaterialType = CustomDropDownItem(
    index: 0,
    title: UiUtils.getTranslatedLabel(context, fileUploadKey),
  );

  late final TextEditingController _fileNameEditingController =
      TextEditingController();

  late final TextEditingController _youtubeLinkEditingController =
      TextEditingController();

  PlatformFile? addedFile; //if studymaterial type is fileUpload
  PlatformFile?
  addedVideoThumbnailFile; //if studymaterial type is not fileUpload
  PlatformFile? addedVideoFile; //if studymaterial type is videoUpload

  @override
  void initState() {
    super.initState();
    Future.delayed(Duration.zero, () {
      if (context.mounted) {
        if (widget.editFileDetails) {
          _fileNameEditingController.text =
              widget.pickedStudyMaterial!.fileName;
          if (widget.pickedStudyMaterial!.pickedStudyMaterialTypeId == 1) {
            currentSelectedStudyMaterialType = CustomDropDownItem(
              index: 0,
              title: UiUtils.getTranslatedLabel(context, fileUploadKey),
            );

            addedFile = widget.pickedStudyMaterial!.studyMaterialFile;
          } else if (widget.pickedStudyMaterial!.pickedStudyMaterialTypeId ==
              2) {
            currentSelectedStudyMaterialType = CustomDropDownItem(
              index: 0,
              title: UiUtils.getTranslatedLabel(context, youtubeLinkKey),
            );
            addedVideoThumbnailFile =
                widget.pickedStudyMaterial!.videoThumbnailFile;
            _youtubeLinkEditingController.text =
                widget.pickedStudyMaterial!.youTubeLink!;
          } else {
            currentSelectedStudyMaterialType = CustomDropDownItem(
              index: 0,
              title: UiUtils.getTranslatedLabel(context, videoUploadKey),
            );
            addedVideoThumbnailFile =
                widget.pickedStudyMaterial!.videoThumbnailFile;
            addedVideoFile = widget.pickedStudyMaterial!.studyMaterialFile;
          }
        }
        setState(() {});
      }
    });
  }

  @override
  void dispose() {
    _fileNameEditingController.dispose();
    _youtubeLinkEditingController.dispose();
    super.dispose();
  }

  void showErrorMessage(String messageKey) {
    UiUtils.showBottomToastOverlay(
      context: context,
      errorMessage: UiUtils.getTranslatedLabel(context, messageKey),
      backgroundColor: Theme.of(context).colorScheme.error,
    );
  }

  void addStudyMaterial() {
    FocusManager.instance.primaryFocus?.unfocus();
    final pickedStudyMaterialId = UiUtils.getStudyMaterialId(
      currentSelectedStudyMaterialType.title,
      context,
    );

    if (_fileNameEditingController.text.trim().isEmpty) {
      showErrorMessage(pleaseEnterStudyMaterialNameKey);
      return;
    }

    if (pickedStudyMaterialId == 1 && addedFile == null) {
      showErrorMessage(pleaseSelectFileKey);
      return;
    }

    if (pickedStudyMaterialId != 1 && addedVideoThumbnailFile == null) {
      showErrorMessage(pleaseSelectThumbnailImageKey);
      return;
    }

    if (pickedStudyMaterialId == 2 &&
        (_youtubeLinkEditingController.text.trim().isEmpty ||
            !Uri.parse(_youtubeLinkEditingController.text.trim()).isAbsolute)) {
      showErrorMessage(pleaseEnterYoutubeLinkKey);
      return;
    }

    if (pickedStudyMaterialId == 3 && addedVideoFile == null) {
      showErrorMessage(pleaseSelectVideoKey);
      return;
    }

    widget.onTapSubmit(
      PickedStudyMaterial(
        fileName: _fileNameEditingController.text.trim(),
        pickedStudyMaterialTypeId: pickedStudyMaterialId,
        studyMaterialFile: pickedStudyMaterialId == 1
            ? addedFile
            : addedVideoFile,
        videoThumbnailFile: addedVideoThumbnailFile,
        youTubeLink: _youtubeLinkEditingController.text.trim(),
      ),
    );
    Navigator.of(context).pop();
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: MediaQuery.of(context).viewInsets,
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            //
            BottomSheetTopBarMenu(
              onTapCloseButton: () {
                Navigator.of(context).pop();
              },
              title: UiUtils.getTranslatedLabel(
                context,
                widget.editFileDetails
                    ? editStudyMaterialKey
                    : addStudyMaterialKey,
              ),
            ),

            //
            Padding(
              padding: EdgeInsets.symmetric(
                horizontal: UiUtils.bottomSheetHorizontalContentPadding,
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  //Study material type dropdown list
                  KeyboardVisibilityBuilder(
                    builder: (context, isKeyboardVisible) {
                      return IgnorePointer(
                        ignoring: isKeyboardVisible,
                        child: CustomDropDownMenu(
                          onChanged: (value) {
                            if (value != null &&
                                value != currentSelectedStudyMaterialType) {
                              setState(() {
                                currentSelectedStudyMaterialType = value;
                                addedFile = null;
                                addedVideoFile = null;
                                addedVideoThumbnailFile = null;
                              });
                            }
                          },
                          textStyle: TextStyle(
                            color: Theme.of(
                              context,
                            ).colorScheme.onSurfaceVariant,
                            fontSize: UiUtils.textFieldFontSize,
                          ),
                          borderRadius: 10,
                          height: 50,
                          width: double.maxFinite,
                          menu: [
                            UiUtils.getTranslatedLabel(context, fileUploadKey),
                            UiUtils.getTranslatedLabel(context, youtubeLinkKey),
                            UiUtils.getTranslatedLabel(context, videoUploadKey),
                          ],
                          currentSelectedItem: currentSelectedStudyMaterialType,
                        ),
                      );
                    },
                  ),
                  //
                  //File name
                  //
                  BottomSheetTextFieldContainer(
                    margin: const EdgeInsets.only(bottom: 25),
                    hintText: UiUtils.getTranslatedLabel(
                      context,
                      studyMaterialNameKey,
                    ),
                    maxLines: 1,
                    textEditingController: _fileNameEditingController,
                  ),

                  //
                  //Select file picker. If study material type is fileUpload then it will pick file
                  //else it will pick video thumbnail image
                  //

                  //
                  //if file or images has been picked then show the pickedFile name and remove button
                  //else show file picker option
                  //
                  if (addedFile != null)
                    AddedFileContainer(
                      platformFile: addedFile!,
                      onDelete: () {
                        addedFile = null;
                        setState(() {});
                      },
                    )
                  else
                    addedVideoThumbnailFile != null
                        ? AddedFileContainer(
                            platformFile: addedVideoThumbnailFile!,
                            onDelete: () {
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
                                final pickedFile = await FilePicker.pickFiles(
                                  type:
                                      currentSelectedStudyMaterialType.title ==
                                          UiUtils.getTranslatedLabel(
                                            context,
                                            fileUploadKey,
                                          )
                                      ? FileType.any
                                      : FileType.image,
                                );
                                //
                                //
                                if (pickedFile != null) {
                                  //if current selected study material type is file

                                  if (context.mounted &&
                                      currentSelectedStudyMaterialType.title ==
                                          UiUtils.getTranslatedLabel(
                                            context,
                                            fileUploadKey,
                                          )) {
                                    addedFile = pickedFile.files.first;
                                  } else {
                                    addedVideoThumbnailFile =
                                        pickedFile.files.first;
                                  }

                                  setState(() {});
                                }
                              } on Exception {
                                showErrorMessage(permissionToPickFileKey);
                                await Future.delayed(
                                  const Duration(seconds: 2),
                                );
                                openAppSettings();
                              }
                            },
                            title:
                                currentSelectedStudyMaterialType.title ==
                                    UiUtils.getTranslatedLabel(
                                      context,
                                      fileUploadKey,
                                    )
                                ? UiUtils.getTranslatedLabel(
                                    context,
                                    selectFileKey,
                                  )
                                : UiUtils.getTranslatedLabel(
                                    context,
                                    selectThumbnailKey,
                                  ),
                          ),

                  const SizedBox(height: 25),

                  if (currentSelectedStudyMaterialType.title ==
                      UiUtils.getTranslatedLabel(context, youtubeLinkKey))
                    BottomSheetTextFieldContainer(
                      margin: const EdgeInsets.only(bottom: 25),
                      hintText: UiUtils.getTranslatedLabel(
                        context,
                        youtubeLinkKey,
                      ),
                      maxLines: 1,
                      textEditingController: _youtubeLinkEditingController,
                    )
                  else
                    currentSelectedStudyMaterialType.title ==
                            UiUtils.getTranslatedLabel(context, videoUploadKey)
                        ? addedVideoFile != null
                              ? AddedFileContainer(
                                  platformFile: addedVideoFile!,
                                  onDelete: () {
                                    addedVideoFile = null;
                                    setState(() {});
                                  },
                                )
                              : BottomsheetAddFilesDottedBorderContainer(
                                  onTap: () async {
                                    if (UiUtils.showMobileOnlyNotice(context)) {
                                      return;
                                    }
                                    try {
                                      final pickedFile =
                                          await FilePicker.pickFiles(
                                            type: FileType.video,
                                          );

                                      if (pickedFile != null) {
                                        addedVideoFile = pickedFile.files.first;
                                        setState(() {});
                                      }
                                    } on Exception {
                                      showErrorMessage(permissionToPickFileKey);
                                      await Future.delayed(
                                        const Duration(seconds: 2),
                                      );
                                      openAppSettings();
                                    }
                                  },
                                  title:
                                      currentSelectedStudyMaterialType.title ==
                                          UiUtils.getTranslatedLabel(
                                            context,
                                            fileUploadKey,
                                          )
                                      ? UiUtils.getTranslatedLabel(
                                          context,
                                          selectFileKey,
                                        )
                                      : UiUtils.getTranslatedLabel(
                                          context,
                                          selectVideoKey,
                                        ),
                                )
                        : const SizedBox(),

                  const SizedBox(height: 25),

                  CustomRoundedButton(
                    onTap: () {
                      addStudyMaterial();
                    },
                    height: UiUtils.bottomSheetButtonHeight,
                    widthPercentage: UiUtils.bottomSheetButtonWidthPercentage,
                    backgroundColor: Theme.of(context).colorScheme.primary,
                    buttonTitle: UiUtils.getTranslatedLabel(context, submitKey),
                    showBorder: false,
                  ),
                ],
              ),
            ),
            const SizedBox(height: 25),
          ],
        ),
      ),
    );
  }
}
