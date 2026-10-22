import 'dart:io';
import 'package:eschool_teacher/cubits/appConfigurationCubit.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/utils/assets.dart';
import 'package:eschool_teacher/utils/errorMessageKeysAndCodes.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_svg/svg.dart';
import 'package:image_picker/image_picker.dart';

enum AttachmentFileSelectionType { camera, gallery, document }

class AttachmentDialogWidget extends StatefulWidget {
  const AttachmentDialogWidget({
    required this.onItemSelected,
    required this.onCancel,
    super.key,
  });
  final Function(List<String> selectedFilePaths, bool isImage) onItemSelected;
  final Function() onCancel;

  @override
  State<AttachmentDialogWidget> createState() => _AttachmentDialogWidgetState();
}

class _AttachmentDialogWidgetState extends State<AttachmentDialogWidget> {
  AttachmentFileSelectionType selectionType =
      AttachmentFileSelectionType.camera;
  List<String> selectedFilePaths = [];
  bool loading = false;

  late final ChatSettings chatSettings = context
      .read<AppConfigurationCubit>()
      .getAppConfiguration()
      .chatSettings;

  Future<void> onFilesPicked() async {
    if (UiUtils.showMobileOnlyNotice(context)) {
      return;
    }
    setState(() {
      loading = true;
    });
    try {
      List<String> tempPaths = [];
      if (selectionType == AttachmentFileSelectionType.document) {
        //document picking logic
        final FilePickerResult? result = await FilePicker.pickFiles(
          allowMultiple: true,
        );
        if (result != null) {
          tempPaths = result.files.map((e) => e.path ?? '').toList();
        }
      } else if (selectionType == AttachmentFileSelectionType.gallery) {
        //gallery picking logic
        final ImagePicker picker = ImagePicker();
        final result = await picker.pickMultiImage();
        if (result.isNotEmpty) {
          tempPaths = result.map<String>((e) => e.path).toList();
        }
      } else {
        //camera picking logic
        final ImagePicker picker = ImagePicker();
        final result = await picker.pickImage(source: ImageSource.camera);
        if (result != null) {
          tempPaths = [result.path];
        }
      }
      int totalNumberOfFilesRemovedBecauseOfMaxNumberLimit = 0;
      int totalNumberOfFilesRemovedBecauseOfMaxSizeLimit = 0;
      for (int i = 0; i < tempPaths.length; i++) {
        if (!selectedFilePaths.contains(tempPaths[i])) {
          //add if already not added the same file with size and total count validations
          if (selectedFilePaths.length >=
              chatSettings.maxFilesOrImagesInOneMessage) {
            totalNumberOfFilesRemovedBecauseOfMaxNumberLimit++;
          } else if ((await File(tempPaths[i]).length()) >=
              chatSettings.maxFileSizeInBytesCanBeSent) {
            totalNumberOfFilesRemovedBecauseOfMaxSizeLimit++;
          } else {
            selectedFilePaths.add(tempPaths[i]);
          }
        }
      }
      if (totalNumberOfFilesRemovedBecauseOfMaxNumberLimit != 0 ||
          totalNumberOfFilesRemovedBecauseOfMaxSizeLimit != 0) {
        if (context.mounted) {
          UiUtils.showBottomToastOverlay(
            context: context,
            errorMessage:
                "${totalNumberOfFilesRemovedBecauseOfMaxNumberLimit != 0 ? "${UiUtils.getTranslatedLabel(context, maxAllowedFilesAreKey)} ${chatSettings.maxFilesOrImagesInOneMessage}, ${UiUtils.getTranslatedLabel(context, removedKey)} $totalNumberOfFilesRemovedBecauseOfMaxNumberLimit ${UiUtils.getTranslatedLabel(context, extraKey)}." : ""} ${totalNumberOfFilesRemovedBecauseOfMaxSizeLimit != 0 ? "${UiUtils.getTranslatedLabel(context, removedKey)} $totalNumberOfFilesRemovedBecauseOfMaxSizeLimit ${UiUtils.getTranslatedLabel(context, filesAsTheyExceededTheLimitOfKey)} ${UiUtils.getFileSizeString(bytes: chatSettings.maxFileSizeInBytesCanBeSent)}." : ""}",
            backgroundColor: Theme.of(context).colorScheme.error,
          );
        }
      }
    } on PlatformException catch (_) {
      if (context.mounted) {
        UiUtils.showBottomToastOverlay(
          context: context,
          errorMessage: UiUtils.getTranslatedLabel(
            context,
            ErrorMessageKeysAndCode.getErrorMessageKeyFromCode(
              ErrorMessageKeysAndCode.permissionNotGivenCode,
            ),
          ),
          backgroundColor: Theme.of(context).colorScheme.error,
        );
      }
    } catch (_) {
      if (context.mounted) {
        UiUtils.showBottomToastOverlay(
          context: context,
          errorMessage: UiUtils.getTranslatedLabel(
            context,
            ErrorMessageKeysAndCode.getErrorMessageKeyFromCode(
              ErrorMessageKeysAndCode.defaultErrorMessageCode,
            ),
          ),
          backgroundColor: Theme.of(context).colorScheme.error,
        );
      }
    }
    loading = false;
    setState(() {});
  }

  void onClickSend() {
    widget.onItemSelected(
      selectedFilePaths,
      selectionType == AttachmentFileSelectionType.document ? false : true,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(12),
        color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.05),
      ),
      padding: const EdgeInsets.all(10),
      child: loading
          ? Container(
              height: 100,
              alignment: Alignment.center,
              child: SizedBox(
                height: 30,
                width: 30,
                child: CustomCircularProgressIndicator(
                  indicatorColor: Theme.of(context).colorScheme.primary,
                ),
              ),
            )
          : selectedFilePaths.isEmpty
          ? SizedBox(
              height: 100,
              child: Row(
                children:
                    [
                      {
                        'image': Assets.documentAttach,
                        'text': UiUtils.getTranslatedLabel(
                          context,
                          documentKey,
                        ),
                        'onTap': () {
                          selectionType = AttachmentFileSelectionType.document;
                          onFilesPicked();
                        },
                      },
                      {
                        'image': Assets.cameraImageAttach,
                        'text': UiUtils.getTranslatedLabel(context, cameraKey),
                        'onTap': () {
                          selectionType = AttachmentFileSelectionType.camera;
                          onFilesPicked();
                        },
                      },
                      {
                        'image': Assets.galleryImageAttach,
                        'text': UiUtils.getTranslatedLabel(context, galleryKey),
                        'onTap': () {
                          selectionType = AttachmentFileSelectionType.gallery;
                          onFilesPicked();
                        },
                      },
                    ].map<Widget>((e) {
                      return Expanded(
                        child: InkWell(
                          onTap: e['onTap']! as Function(),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              Expanded(
                                child: Center(
                                  child: Container(
                                    width: 60,
                                    height: 60,
                                    decoration: BoxDecoration(
                                      color: Theme.of(
                                        context,
                                      ).colorScheme.primary,
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    padding: const EdgeInsets.all(15),
                                    child: SvgPicture.asset(
                                      e['image']! as String,
                                      fit: BoxFit.cover,
                                      colorFilter: ColorFilter.mode(
                                        Theme.of(context).colorScheme.onPrimary,
                                        BlendMode.srcIn,
                                      ),
                                    ),
                                  ),
                                ),
                              ),
                              Text(
                                e['text']! as String,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(
                                  color: Theme.of(context).colorScheme.onSurface
                                      .withValues(alpha: 0.7),
                                ),
                                textAlign: TextAlign.center,
                              ),
                            ],
                          ),
                        ),
                      );
                    }).toList(),
              ),
            )
          : Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 10),
                  child: Row(
                    children: [
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          '${UiUtils.getTranslatedLabel(context, selectionType == AttachmentFileSelectionType.camera
                              ? camaraImagesKey
                              : selectionType == AttachmentFileSelectionType.document
                              ? selectedFilesKey
                              : pickedImages)} (${selectedFilePaths.length})',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: Theme.of(context).colorScheme.primary,
                          ),
                          textAlign: TextAlign.start,
                        ),
                      ),
                      const SizedBox(width: 5),
                      InkWell(
                        onTap: () {
                          onFilesPicked();
                        },
                        child: Icon(
                          Icons.add,
                          color: Theme.of(context).colorScheme.primary,
                        ),
                      ),
                      const SizedBox(width: 10),
                    ],
                  ),
                ),
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children:
                        selectionType == AttachmentFileSelectionType.document
                        ? List.generate(selectedFilePaths.length, (index) {
                            //normal selected file UI
                            return SizedBox(
                              height: 100,
                              width: 150,
                              child: Padding(
                                padding: const EdgeInsets.all(5),
                                child: Stack(
                                  children: [
                                    Container(
                                      decoration: BoxDecoration(
                                        borderRadius: BorderRadius.circular(12),
                                        color: Theme.of(
                                          context,
                                        ).colorScheme.primary,
                                      ),
                                      padding: const EdgeInsets.all(5),
                                      height: 100,
                                      width: 150,
                                      alignment: Alignment.center,
                                      child: Column(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          SvgPicture.asset(
                                            Assets.documentAttach,
                                            width: 25,
                                            height: 25,
                                            colorFilter: ColorFilter.mode(
                                              Theme.of(
                                                context,
                                              ).colorScheme.onPrimary,
                                              BlendMode.srcIn,
                                            ),
                                          ),
                                          const SizedBox(height: 5),
                                          Text(
                                            selectedFilePaths[index]
                                                .split('/')
                                                .last,
                                            style: TextStyle(
                                              color: Theme.of(
                                                context,
                                              ).colorScheme.onPrimary,
                                              fontWeight: FontWeight.bold,
                                              fontSize: 12,
                                            ),
                                            maxLines: 2,
                                            textAlign: TextAlign.center,
                                            overflow: TextOverflow.ellipsis,
                                          ),
                                        ],
                                      ),
                                    ),
                                    Align(
                                      alignment: AlignmentDirectional.topEnd,
                                      child: Container(
                                        margin: const EdgeInsets.all(5),
                                        padding: const EdgeInsets.all(2),
                                        decoration: BoxDecoration(
                                          shape: BoxShape.circle,
                                          color: Theme.of(context)
                                              .colorScheme
                                              .onPrimary
                                              .withValues(alpha: 0.24),
                                        ),
                                        child: InkWell(
                                          onTap: () {
                                            selectedFilePaths.removeAt(index);
                                            setState(() {});
                                          },
                                          child: Icon(
                                            Icons.close_rounded,
                                            color: Theme.of(
                                              context,
                                            ).colorScheme.error,
                                            size: 18,
                                          ),
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            );
                          })
                        : List.generate(selectedFilePaths.length, (index) {
                            //image files selected UI
                            return SizedBox(
                              height: 100,
                              width: 130,
                              child: Padding(
                                padding: const EdgeInsets.all(5),
                                child: Stack(
                                  children: [
                                    Container(
                                      decoration: BoxDecoration(
                                        borderRadius: BorderRadius.circular(12),
                                        color: Theme.of(
                                          context,
                                        ).colorScheme.primary,
                                      ),
                                      height: 100,
                                      width: 130,
                                      clipBehavior: Clip.antiAlias,
                                      child: Image.file(
                                        File(selectedFilePaths[index]),
                                        fit: BoxFit.cover,
                                      ),
                                    ),
                                    Align(
                                      alignment: AlignmentDirectional.topEnd,
                                      child: Container(
                                        margin: const EdgeInsets.all(5),
                                        padding: const EdgeInsets.all(2),
                                        decoration: BoxDecoration(
                                          shape: BoxShape.circle,
                                          color: Theme.of(context)
                                              .colorScheme
                                              .onPrimary
                                              .withValues(alpha: 0.24),
                                        ),
                                        child: InkWell(
                                          onTap: () {
                                            selectedFilePaths.removeAt(index);
                                            setState(() {});
                                          },
                                          child: Icon(
                                            Icons.close_rounded,
                                            color: Theme.of(
                                              context,
                                            ).colorScheme.error,
                                            size: 18,
                                          ),
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            );
                          }),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 10),
                  child: Row(
                    children: [
                      const SizedBox(width: 10),
                      Expanded(
                        child: CustomRoundedButton(
                          onTap: () {
                            widget.onCancel();
                          },
                          widthPercentage: 0.3,
                          height: 40,
                          textAlign: TextAlign.center,
                          backgroundColor: Colors.transparent,
                          buttonTitle: UiUtils.getTranslatedLabel(
                            context,
                            cancelKey,
                          ),
                          titleColor: UiUtils.getColorScheme(context).primary,
                          showBorder: true,
                          borderColor: UiUtils.getColorScheme(context).primary,
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: CustomRoundedButton(
                          onTap: () {
                            onClickSend();
                          },
                          widthPercentage: 0.3,
                          height: 40,
                          textAlign: TextAlign.center,
                          backgroundColor: UiUtils.getColorScheme(
                            context,
                          ).primary,
                          buttonTitle: UiUtils.getTranslatedLabel(
                            context,
                            sendKey,
                          ),
                          titleColor: Theme.of(context).colorScheme.onPrimary,
                          showBorder: false,
                        ),
                      ),
                      const SizedBox(width: 10),
                    ],
                  ),
                ),
              ],
            ),
    );
  }
}
