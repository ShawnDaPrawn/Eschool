import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/cubits/announcementsCubit.dart';
import 'package:eschool_teacher/cubits/dashboardCubit.dart';
import 'package:eschool_teacher/cubits/subjectsOfClassSectionCubit.dart';
import 'package:eschool_teacher/data/models/subject.dart';
import 'package:eschool_teacher/data/repositories/announcementRepository.dart';
import 'package:eschool_teacher/data/repositories/teacherRepository.dart';
import 'package:eschool_teacher/ui/widgets/announcementsContainer.dart';
import 'package:eschool_teacher/ui/widgets/classSubjectsDropDownMenu.dart';
import 'package:eschool_teacher/ui/widgets/customAppbar.dart';
import 'package:eschool_teacher/ui/widgets/customDropDownMenu.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/ui/widgets/myClassesDropDownMenu.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class AnnouncementsScreen extends StatefulWidget {
  const AnnouncementsScreen({super.key});

  @override
  State<AnnouncementsScreen> createState() => _AnnouncementsScreenState();

  static Route<dynamic> route(RouteSettings routeSettings) {
    return CupertinoPageRoute(
      builder: (_) => MultiBlocProvider(
        providers: [
          BlocProvider(
            create: (context) =>
                SubjectsOfClassSectionCubit(TeacherRepository()),
          ),
          BlocProvider(
            create: (context) => AnnouncementsCubit(AnnouncementRepository()),
          ),
        ],
        child: const AnnouncementsScreen(),
      ),
    );
  }
}

class _AnnouncementsScreenState extends State<AnnouncementsScreen> {
  late CustomDropDownItem currentSelectedClassSection = CustomDropDownItem(
    index: 0,
    title: context.read<DashboardCubit>().getClassSectionName().first,
  );

  late CustomDropDownItem currentSelectedSubject = CustomDropDownItem(
    index: 0,
    title: UiUtils.getTranslatedLabel(context, fetchingSubjectsKey),
  );

  late final ScrollController _scrollController = ScrollController()
    ..addListener(_announcementsScrollListener);

  void _announcementsScrollListener() {
    if (_scrollController.offset ==
        _scrollController.position.maxScrollExtent) {
      if (context.read<AnnouncementsCubit>().hasMore()) {
        final subject = context
            .read<SubjectsOfClassSectionCubit>()
            .getSubjectDetails(currentSelectedSubject.index);
        context.read<AnnouncementsCubit>().fetchMoreAnnouncements(
          subjectId: subject.id,
          semesterId: subject.semester?.id,
          classSectionId: context
              .read<DashboardCubit>()
              .getClassSectionDetails(index: currentSelectedClassSection.index)
              .id,
        );
      }
    }
  }

  @override
  void initState() {
    context.read<SubjectsOfClassSectionCubit>().fetchSubjects(
      context
          .read<DashboardCubit>()
          .getClassSectionDetails(index: currentSelectedClassSection.index)
          .id,
    );
    super.initState();
  }

  void fetchAnnouncements() {
    final subjectState = context.read<SubjectsOfClassSectionCubit>().state;

    if (subjectState is SubjectsOfClassSectionFetchSuccess &&
        subjectState.subjects.isNotEmpty) {
      final subjectId = context
          .read<SubjectsOfClassSectionCubit>()
          .getSubjectDetails(currentSelectedSubject.index)
          .id;
      if (subjectId != -1) {
        final subject = context
            .read<SubjectsOfClassSectionCubit>()
            .getSubjectDetails(currentSelectedSubject.index);
        context.read<AnnouncementsCubit>().fetchAnnouncements(
          classSectionId: context
              .read<DashboardCubit>()
              .getClassSectionDetails(index: currentSelectedClassSection.index)
              .id,
          subjectId: subjectId,
          semesterId: subject.semester?.id,
        );
      }
    }
  }

  Widget _buildClassAndSubjectDropDowns() {
    return LayoutBuilder(
      builder: (context, boxConstraints) {
        return Column(
          children: [
            MyClassesDropDownMenu(
              currentSelectedItem: currentSelectedClassSection,
              width: boxConstraints.maxWidth,
              changeSelectedItem: (result) {
                setState(() {
                  currentSelectedClassSection = result;
                });

                context.read<AnnouncementsCubit>().updateState(
                  AnnouncementsInitial(),
                );
              },
            ),
            BlocListener<
              SubjectsOfClassSectionCubit,
              SubjectsOfClassSectionState
            >(
              listener: (context, state) {
                if (state is SubjectsOfClassSectionFetchSuccess) {
                  if (state.subjects.isEmpty) {
                    context.read<AnnouncementsCubit>().updateState(
                      AnnouncementsFetchSuccess(
                        announcements: [],
                        fetchMoreAnnouncementsInProgress: false,
                        moreAnnouncementsFetchError: false,
                        totalPage: 0,
                        currentPage: 0,
                      ),
                    );
                  }
                }
              },
              child: ClassSubjectsDropDownMenu(
                changeSelectedItem: (result) {
                  setState(() {
                    currentSelectedSubject = result;
                  });
                  fetchAnnouncements();
                },
                currentSelectedItem: currentSelectedSubject,
                width: boxConstraints.maxWidth,
              ),
            ),
          ],
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      floatingActionButton: FloatingActionAddButton(
        onTap: () {
          Navigator.of(
            context,
          ).pushNamed<bool?>(Routes.addOrEditAnnouncement).then((value) {
            if (value != null && value) {
              fetchAnnouncements();
            }
          });
        },
      ),
      body: Stack(
        children: [
          CustomRefreshIndicator(
            displacement: UiUtils.getScrollViewTopPadding(
              context: context,
              appBarHeightPercentage: UiUtils.appBarSmallerHeightPercentage,
            ),
            onRefreshCallback: () {
              fetchAnnouncements();
            },
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              controller: _scrollController,
              padding: EdgeInsets.only(
                left:
                    MediaQuery.sizeOf(context).width *
                    UiUtils.screenContentHorizontalPaddingPercentage,
                right:
                    MediaQuery.sizeOf(context).width *
                    UiUtils.screenContentHorizontalPaddingPercentage,
                top: UiUtils.getScrollViewTopPadding(
                  context: context,
                  appBarHeightPercentage: UiUtils.appBarSmallerHeightPercentage,
                ),
              ),
              children: [
                _buildClassAndSubjectDropDowns(),
                SizedBox(height: MediaQuery.sizeOf(context).height * 0.0125),
                AnnouncementsContainer(
                  classSectionDetails: context
                      .read<DashboardCubit>()
                      .getClassSectionDetails(
                        index: currentSelectedClassSection.index,
                      ),
                  subject:
                      (context.read<SubjectsOfClassSectionCubit>().state
                              is SubjectsOfClassSectionFetchSuccess &&
                          (context.read<SubjectsOfClassSectionCubit>().state
                                  as SubjectsOfClassSectionFetchSuccess)
                              .subjects
                              .isNotEmpty)
                      ? context
                            .read<SubjectsOfClassSectionCubit>()
                            .getSubjectDetails(currentSelectedSubject.index)
                      : Subject.fromJson({}),
                ),
              ],
            ),
          ),
          Align(
            alignment: Alignment.topCenter,
            child: CustomAppBar(
              title: UiUtils.getTranslatedLabel(context, announcementsKey),
            ),
          ),
        ],
      ),
    );
  }
}
