import 'package:eschool_teacher/cubits/createAnnouncementCubit.dart';
import 'package:eschool_teacher/cubits/dashboardCubit.dart';
import 'package:eschool_teacher/cubits/editAnnouncementCubit.dart';
import 'package:eschool_teacher/cubits/subjectsOfClassSectionCubit.dart';
import 'package:eschool_teacher/data/models/announcement.dart';
import 'package:eschool_teacher/data/models/classSectionDetails.dart';
import 'package:eschool_teacher/data/models/studyMaterial.dart';
import 'package:eschool_teacher/data/models/subject.dart';
import 'package:eschool_teacher/data/repositories/announcementRepository.dart';
import 'package:eschool_teacher/data/repositories/teacherRepository.dart';
import 'package:eschool_teacher/ui/widgets/addedFileContainer.dart';
import 'package:eschool_teacher/ui/widgets/announcementAttachmentContainer.dart';
import 'package:eschool_teacher/ui/widgets/bottomSheetTextFiledContainer.dart';
import 'package:eschool_teacher/ui/widgets/bottomsheetAddFilesDottedBorderContainer.dart';
import 'package:eschool_teacher/ui/widgets/classSubjectsDropDownMenu.dart';
import 'package:eschool_teacher/ui/widgets/customAppbar.dart';
import 'package:eschool_teacher/ui/widgets/customCircularProgressIndicator.dart';
import 'package:eschool_teacher/ui/widgets/customDropDownMenu.dart';
import 'package:eschool_teacher/ui/widgets/customRoundedButton.dart';
import 'package:eschool_teacher/ui/widgets/defaultDropDownLabelContainer.dart';
import 'package:eschool_teacher/ui/widgets/myClassesDropDownMenu.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:permission_handler/permission_handler.dart';

class AddOrEditAnnouncementScreen extends StatefulWidget {
  const AddOrEditAnnouncementScreen({
    super.key,
    this.classSectionDetails,
    this.announcement,
    this.subject,
  });
  final Announcement? announcement;
  final ClassSectionDetails? classSectionDetails;
  final Subject? subject;

  static Route<bool?> route(RouteSettings routeSettings) {
    final arguments =
        (routeSettings.arguments ?? Map<String, dynamic>.from({}))
            as Map<String, dynamic>;
    return CupertinoPageRoute(
      builder: (_) => MultiBlocProvider(
        providers: [
          BlocProvider(
            create: (context) =>
                SubjectsOfClassSectionCubit(TeacherRepository()),
          ),
          BlocProvider(
            create: (context) =>
                CreateAnnouncementCubit(AnnouncementRepository()),
          ),
          BlocProvider(
            create: (context) =>
                EditAnnouncementCubit(AnnouncementRepository()),
          ),
        ],
        child: AddOrEditAnnouncementScreen(
          announcement: arguments['announcement'],
          subject: arguments['subject'],
          classSectionDetails: arguments['classSectionDetails'],
        ),
      ),
    );
  }

  @override
  State<AddOrEditAnnouncementScreen> createState() =>
      _AddOrEditAnnouncementScreenState();
}

class _AddOrEditAnnouncementScreenState
    extends State<AddOrEditAnnouncementScreen> {
  late CustomDropDownItem currentSelectedClassSection = CustomDropDownItem(
    index: 0,
    title: widget.classSectionDetails != null
        ? widget.classSectionDetails!.getClassSectionNameWithMedium()
        : context.read<DashboardCubit>().getClassSectionName().first,
  );

  late CustomDropDownItem currentSelectedSubject = widget.subject != null
      ? CustomDropDownItem(
          index: 0,
          title: widget.subject!.subjectNameWithTypeAndSemester,
        )
      : CustomDropDownItem(
          index: 0,
          title: UiUtils.getTranslatedLabel(context, fetchingSubjectsKey),
        );

  late final TextEditingController _announcementTitleEditingController =
      TextEditingController(text: widget.announcement?.title);
  late final TextEditingController _announcementDescriptionEditingController =
      TextEditingController(text: widget.announcement?.description);

  List<PlatformFile> _addedAttatchments = [];

  late List<StudyMaterial> attatchments = widget.announcement != null
      ? widget.announcement!.files
      : [];

  //This will determine if need to refresh the previous page
  //lessons data. If teacher remove the the any study material
  //so we need to fetch the list again
  late bool refreshAnnouncementsInPreviousPage = false;

  @override
  void initState() {
    if (widget.classSectionDetails == null) {
      context.read<SubjectsOfClassSectionCubit>().fetchSubjects(
        context.read<DashboardCubit>().getAllClasses().first.id,
      );
    }
    super.initState();
  }

  void deleteAttachment(int attachmentId) {
    attatchments.removeWhere((element) => element.id == attachmentId);

    refreshAnnouncementsInPreviousPage = true;
    setState(() {});
  }

  Future<void> addAttachment() async {
    final permission = await Permission.storage.request();
    if (permission.isGranted) {
      final pickedFile = await FilePicker.pickFiles();
      if (pickedFile != null) {
        _addedAttatchments.add(pickedFile.files.first);
        setState(() {});
      }
    } else {
      try {
        final pickedFile = await FilePicker.pickFiles();
        if (pickedFile != null) {
          _addedAttatchments.add(pickedFile.files.first);
          setState(() {});
        }
      } on Exception {
        if (context.mounted) {
          UiUtils.showBottomToastOverlay(
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

  void createAnnouncement() {
    FocusManager.instance.primaryFocus?.unfocus();
    bool isAnySubjectAvailable = false;
    if (context.read<SubjectsOfClassSectionCubit>().state
            is SubjectsOfClassSectionFetchSuccess &&
        (context.read<SubjectsOfClassSectionCubit>().state
                as SubjectsOfClassSectionFetchSuccess)
            .subjects
            .isNotEmpty) {
      isAnySubjectAvailable = true;
    }
    if (!isAnySubjectAvailable && widget.subject == null) {
      UiUtils.showBottomToastOverlay(
        context: context,
        errorMessage: UiUtils.getTranslatedLabel(context, noSubjectSelectedKey),
        backgroundColor: Theme.of(context).colorScheme.error,
      );
      return;
    }
    if (_announcementTitleEditingController.text.trim().isEmpty) {
      UiUtils.showBottomToastOverlay(
        context: context,
        errorMessage: UiUtils.getTranslatedLabel(
          context,
          pleaseAddAnnouncementTitleKey,
        ),
        backgroundColor: Theme.of(context).colorScheme.error,
      );
      return;
    }
    final selectedSubject = widget.subject != null
        ? widget.subject!
        : context.read<SubjectsOfClassSectionCubit>().getSubjectDetails(
            currentSelectedSubject.index,
          );

    context.read<CreateAnnouncementCubit>().createAnnouncement(
      title: _announcementTitleEditingController.text.trim(),
      description: _announcementDescriptionEditingController.text.trim(),
      attachments: _addedAttatchments,
      classSectionId: widget.classSectionDetails != null
          ? widget.classSectionDetails!.id
          : context
                .read<DashboardCubit>()
                .getClassSectionDetails(
                  index: currentSelectedClassSection.index,
                )
                .id,
      subjectId: selectedSubject.id,
      semesterId: selectedSubject.semester?.id,
    );
  }

  void editAnnouncement() {
    FocusManager.instance.primaryFocus?.unfocus();
    if (_announcementTitleEditingController.text.trim().isEmpty) {
      UiUtils.showBottomToastOverlay(
        context: context,
        errorMessage: UiUtils.getTranslatedLabel(
          context,
          pleaseAddAnnouncementTitleKey,
        ),
        backgroundColor: Theme.of(context).colorScheme.error,
      );
      return;
    }
    context.read<EditAnnouncementCubit>().editAnnouncement(
      announcementId: widget.announcement!.id,
      title: _announcementTitleEditingController.text.trim(),
      description: _announcementDescriptionEditingController.text.trim(),
      attachments: _addedAttatchments,
      classSectionId: widget.classSectionDetails!.id,
      subjectId: widget.subject!.id,
      semesterId: widget.subject!.semester?.id,
    );
  }

  Widget _buildClassAndSubjectDropDowns() {
    return LayoutBuilder(
      builder: (context, boxConstraints) {
        return Column(
          children: [
            if (widget.classSectionDetails == null)
              MyClassesDropDownMenu(
                currentSelectedItem: currentSelectedClassSection,
                width: boxConstraints.maxWidth,
                changeSelectedItem: (result) {
                  setState(() {
                    currentSelectedClassSection = result;
                  });
                },
              )
            else
              DefaultDropDownLabelContainer(
                titleLabelKey: currentSelectedClassSection.title,
                width: boxConstraints.maxWidth,
                highlight: true,
              ),
            if (widget.subject == null)
              ClassSubjectsDropDownMenu(
                changeSelectedItem: (result) {
                  setState(() {
                    currentSelectedSubject = result;
                  });
                },
                currentSelectedItem: currentSelectedSubject,
                width: boxConstraints.maxWidth,
              )
            else
              DefaultDropDownLabelContainer(
                titleLabelKey: currentSelectedSubject.title,
                width: boxConstraints.maxWidth,
                highlight: true,
              ),
          ],
        );
      },
    );
  }

  Widget _buildAnnouncementForm() {
    return SingleChildScrollView(
      padding: EdgeInsets.only(
        bottom: 25,
        right:
            UiUtils.screenContentHorizontalPaddingPercentage *
            MediaQuery.sizeOf(context).width,
        left:
            UiUtils.screenContentHorizontalPaddingPercentage *
            MediaQuery.sizeOf(context).width,
        top: UiUtils.getScrollViewTopPadding(
          context: context,
          appBarHeightPercentage: UiUtils.appBarSmallerHeightPercentage,
        ),
      ),
      child: Column(
        children: [
          _buildClassAndSubjectDropDowns(),
          BottomSheetTextFieldContainer(
            hintText: UiUtils.getTranslatedLabel(context, announcementTitleKey),
            margin: const EdgeInsets.only(bottom: 20),
            maxLines: 1,
            contentPadding: const EdgeInsetsDirectional.only(start: 15),
            textEditingController: _announcementTitleEditingController,
          ),
          BottomSheetTextFieldContainer(
            margin: const EdgeInsets.only(bottom: 20),
            hintText: UiUtils.getTranslatedLabel(
              context,
              announcementDescriptionKey,
            ),
            maxLines: 3,
            contentPadding: const EdgeInsetsDirectional.only(start: 15),
            textEditingController: _announcementDescriptionEditingController,
          ),
          if (widget.announcement != null)
            Column(
              children: attatchments
                  .map(
                    (file) => AnnouncementAttachmentContainer(
                      onDeleteCallback: deleteAttachment,
                      showDeleteButton: true,
                      studyMaterial: file,
                    ),
                  )
                  .toList(),
            )
          else
            const SizedBox(),
          BottomsheetAddFilesDottedBorderContainer(
            onTap: () async {
              FocusScope.of(context).unfocus();
              addAttachment();
            },
            title: UiUtils.getTranslatedLabel(context, attachmentsKey),
          ),
          const SizedBox(height: 20),
          ...List.generate(_addedAttatchments.length, (index) => index).map(
            (index) => Padding(
              padding: const EdgeInsets.only(bottom: 15),
              child: AddedFileContainer(
                onDelete: () {
                  _addedAttatchments.removeAt(index);
                  setState(() {});
                },
                platformFile: _addedAttatchments[index],
              ),
            ),
          ),
          if (widget.announcement != null)
            BlocConsumer<EditAnnouncementCubit, EditAnnouncementState>(
              listener: (context, state) {
                if (state is EditAnnouncementSuccess) {
                  UiUtils.showBottomToastOverlay(
                    context: context,
                    errorMessage: UiUtils.getTranslatedLabel(
                      context,
                      UiUtils.getTranslatedLabel(
                        context,
                        announcementEditedSuccessfullyKey,
                      ),
                    ),
                    backgroundColor: Theme.of(context).colorScheme.onPrimary,
                  );
                  Navigator.of(context).pop(true);
                } else if (state is EditAnnouncementFailure) {
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
                return LayoutBuilder(
                  builder: (context, boxConstraints) {
                    return Padding(
                      padding: EdgeInsets.symmetric(
                        horizontal: boxConstraints.maxWidth * 0.15,
                      ),
                      child: CustomRoundedButton(
                        onTap: () {
                          if (state is EditAnnouncementInProgress) {
                            return;
                          }
                          editAnnouncement();
                        },
                        height: 45,
                        widthPercentage: 1,
                        backgroundColor: Theme.of(context).colorScheme.primary,
                        buttonTitle: UiUtils.getTranslatedLabel(
                          context,
                          editAnnouncementKey,
                        ),
                        showBorder: false,
                        child: state is EditAnnouncementInProgress
                            ? const CustomCircularProgressIndicator(
                                strokeWidth: 2,
                                widthAndHeight: 20,
                              )
                            : null,
                      ),
                    );
                  },
                );
              },
            )
          else
            BlocConsumer<CreateAnnouncementCubit, CreateAnnouncementState>(
              listener: (context, state) {
                if (state is CreateAnnouncementSuccess) {
                  _announcementTitleEditingController.text = '';
                  _announcementDescriptionEditingController.text = '';
                  _addedAttatchments = [];
                  refreshAnnouncementsInPreviousPage = true;
                  setState(() {});
                  UiUtils.showBottomToastOverlay(
                    context: context,
                    errorMessage: UiUtils.getTranslatedLabel(
                      context,
                      announcementAddedKey,
                    ),
                    backgroundColor: Theme.of(context).colorScheme.onPrimary,
                  );
                } else if (state is CreateAnnouncementFailure) {
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
                return LayoutBuilder(
                  builder: (context, boxConstraints) {
                    return Padding(
                      padding: EdgeInsets.symmetric(
                        horizontal: boxConstraints.maxWidth * 0.125,
                      ),
                      child: CustomRoundedButton(
                        onTap: () {
                          //
                          if (state is CreateAnnouncementInProgress) {
                            return;
                          }
                          createAnnouncement();
                        },
                        height: 45,
                        widthPercentage: boxConstraints.maxWidth * 0.45,
                        backgroundColor: Theme.of(context).colorScheme.primary,
                        buttonTitle: UiUtils.getTranslatedLabel(
                          context,
                          addAnnouncementKey,
                        ),
                        showBorder: false,
                        child: state is CreateAnnouncementInProgress
                            ? const CustomCircularProgressIndicator(
                                strokeWidth: 2,
                                widthAndHeight: 20,
                              )
                            : null,
                      ),
                    );
                  },
                );
              },
            ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, dynamic result) {
        if (didPop) {
          return;
        }
        Navigator.of(context).pop(refreshAnnouncementsInPreviousPage);
      },
      child: Scaffold(
        body: Stack(
          children: [
            Align(
              alignment: Alignment.topCenter,
              child: _buildAnnouncementForm(),
            ),
            Align(
              alignment: Alignment.topCenter,
              child: CustomAppBar(
                onPressBackButton: () {
                  if (context.read<EditAnnouncementCubit>().state
                      is EditAnnouncementInProgress) {
                    return;
                  }
                  if (context.read<CreateAnnouncementCubit>().state
                      is CreateAnnouncementInProgress) {
                    return;
                  }
                  Navigator.of(context).pop(refreshAnnouncementsInPreviousPage);
                },
                title: UiUtils.getTranslatedLabel(
                  context,
                  widget.announcement != null
                      ? editAnnouncementKey
                      : addAnnouncementKey,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
