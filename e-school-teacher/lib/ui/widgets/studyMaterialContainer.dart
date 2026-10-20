import 'package:cached_network_image/cached_network_image.dart';
import 'package:eschool_teacher/cubits/deleteStudyMaterialCubit.dart';
import 'package:eschool_teacher/cubits/updateStudyMaterialCubit.dart';
import 'package:eschool_teacher/data/models/studyMaterial.dart';
import 'package:eschool_teacher/data/repositories/studyMaterialRepositoy.dart';
import 'package:eschool_teacher/ui/styles/colors.dart';
import 'package:eschool_teacher/ui/widgets/confirmDeleteDialog.dart';
import 'package:eschool_teacher/ui/widgets/deleteButton.dart';
import 'package:eschool_teacher/ui/widgets/editButton.dart';
import 'package:eschool_teacher/ui/widgets/editStudyMaterialBottomSheet.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class StudyMaterialContainer extends StatelessWidget {

  const StudyMaterialContainer({
    required this.studyMaterial, required this.showEditAndDeleteButton, super.key,
    this.onDeleteStudyMaterial,
    this.onEditStudyMaterial,
  });
  final bool showEditAndDeleteButton;
  final StudyMaterial studyMaterial;
  final Function(int)? onDeleteStudyMaterial;
  final Function(StudyMaterial)? onEditStudyMaterial;

  @override
  Widget build(BuildContext context) {
    final titleTextStyle = TextStyle(
      color: Theme.of(context).colorScheme.secondary,
      fontWeight: FontWeight.w500,
      height: 1.25,
      fontSize: 13.5,
    );

    const subTitleTextStyle = TextStyle(
      color: assignmentViewButtonColor,
      fontSize: 13,
    );

    return BlocProvider(
      create: (context) => DeleteStudyMaterialCubit(StudyMaterialRepository()),
      child: Builder(
        builder: (context) {
          return BlocConsumer<
            DeleteStudyMaterialCubit,
            DeleteStudyMaterialState
          >(
            listener: (context, state) {
              //
              if (state is DeleteStudyMaterialSuccess) {
                onDeleteStudyMaterial?.call(studyMaterial.id);
              } else if (state is DeleteStudyMaterialFailure) {
                UiUtils.showBottomToastOverlay(
                  context: context,
                  //
                  errorMessage: UiUtils.getTranslatedLabel(
                    context,
                    unableToDeleteKey,
                  ),
                  backgroundColor: Theme.of(context).colorScheme.error,
                );
              }
            },
            builder: (context, state) {
              return Opacity(
                opacity: state is DeleteStudyMaterialInProgress ? 0.5 : 1.0,
                child: Container(
                  margin: const EdgeInsets.only(bottom: 25),
                  width: MediaQuery.sizeOf(context).width * 0.85,
                  padding: const EdgeInsets.symmetric(
                    horizontal: 15,
                    vertical: 15,
                  ),
                  decoration: BoxDecoration(
                    color: Theme.of(context).colorScheme.surface,
                    borderRadius: BorderRadius.circular(15),
                  ),
                  child: LayoutBuilder(
                    builder: (context, boxConstraints) {
                      return Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          //Show youtubelink or added file path
                          if (showEditAndDeleteButton)
                            Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                SizedBox(
                                  width: boxConstraints.maxWidth * 0.75,
                                  child: Text(
                                    studyMaterial.fileName,
                                    style: titleTextStyle,
                                    textAlign: TextAlign.start,
                                  ),
                                ),
                                const Spacer(),
                                EditButton(
                                  onTap: () {
                                    if (state
                                        is DeleteStudyMaterialInProgress) {
                                      return;
                                    }
                                    UiUtils.showBottomSheet(
                                      child: BlocProvider(
                                        create: (context) =>
                                            UpdateStudyMaterialCubit(
                                              StudyMaterialRepository(),
                                            ),
                                        child: EditStudyMaterialBottomSheet(
                                          studyMaterial: studyMaterial,
                                        ),
                                      ),
                                      context: context,
                                    ).then((value) {
                                      if (value != null) {
                                        onEditStudyMaterial?.call(
                                          value as StudyMaterial,
                                        );
                                      }
                                    });
                                  },
                                ),
                                const SizedBox(width: 10),
                                DeleteButton(
                                  onTap: () {
                                    if (state
                                        is DeleteStudyMaterialInProgress) {
                                      return;
                                    }
                                    //
                                    showDialog<bool>(
                                      context: context,
                                      builder: (_) =>
                                          const ConfirmDeleteDialog(),
                                    ).then((value) {
                                      if (value != null && value) {
                                        if (context.mounted) {
                                          context
                                              .read<DeleteStudyMaterialCubit>()
                                              .deleteStudyMaterial(
                                                fileId: studyMaterial.id,
                                              );
                                        }
                                      }
                                    });
                                  },
                                ),
                              ],
                            )
                          else
                            Text(
                              studyMaterial.fileName,
                              style: titleTextStyle,
                              textAlign: TextAlign.start,
                            ),

                          if (studyMaterial.studyMaterialType !=
                              StudyMaterialType.youtubeVideo)
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Divider(),
                                Text(
                                  UiUtils.getTranslatedLabel(
                                    context,
                                    filePathKey,
                                  ),
                                  overflow: TextOverflow.ellipsis,
                                  style: titleTextStyle,
                                  textAlign: TextAlign.start,
                                ),
                                GestureDetector(
                                  onTap: () {
                                    UiUtils.viewOrDownloadStudyMaterial(
                                      context: context,
                                      storeInExternalStorage: true,
                                      studyMaterial: studyMaterial,
                                    );
                                  },
                                  child: Text(
                                    '${studyMaterial.fileName}.${studyMaterial.fileExtension}',
                                    style: subTitleTextStyle,
                                  ),
                                ),
                              ],
                            )
                          else
                            const SizedBox(),

                          if (studyMaterial.studyMaterialType ==
                              StudyMaterialType.youtubeVideo)
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Divider(),
                                Text(
                                  UiUtils.getTranslatedLabel(
                                    context,
                                    youtubeLinkKey,
                                  ),
                                  overflow: TextOverflow.ellipsis,
                                  style: titleTextStyle,
                                  textAlign: TextAlign.start,
                                ),
                                GestureDetector(
                                  onTap: () {
                                    UiUtils.viewOrDownloadStudyMaterial(
                                      context: context,
                                      storeInExternalStorage: true,
                                      studyMaterial: studyMaterial,
                                    );
                                  },
                                  child: Text(
                                    studyMaterial.fileUrl,
                                    style: subTitleTextStyle,
                                  ),
                                ),
                              ],
                            )
                          else
                            const SizedBox(),

                          if (studyMaterial.studyMaterialType !=
                              StudyMaterialType.file)
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Divider(),
                                Text(
                                  UiUtils.getTranslatedLabel(
                                    context,
                                    thumbnailImageKey,
                                  ),
                                  overflow: TextOverflow.ellipsis,
                                  style: titleTextStyle,
                                  textAlign: TextAlign.start,
                                ),
                                Container(
                                  margin: const EdgeInsets.only(top: 5),
                                  width: 50,
                                  height: 50,
                                  decoration: BoxDecoration(
                                    color: Theme.of(
                                      context,
                                    ).colorScheme.primary,
                                    image: DecorationImage(
                                      fit: BoxFit.cover,
                                      image: CachedNetworkImageProvider(
                                        studyMaterial.fileThumbnail,
                                      ),
                                    ),
                                    borderRadius: BorderRadius.circular(10),
                                  ),
                                ),
                              ],
                            )
                          else
                            const SizedBox(),
                        ],
                      );
                    },
                  ),
                ),
              );
            },
          );
        },
      ),
    );
  }
}
