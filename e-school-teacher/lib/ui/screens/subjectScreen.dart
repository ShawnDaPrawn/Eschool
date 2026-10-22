import 'package:eschool_teacher/app/routeArguments.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/cubits/announcementsCubit.dart';
import 'package:eschool_teacher/cubits/lessonsCubit.dart';
import 'package:eschool_teacher/data/models/classSectionDetails.dart';
import 'package:eschool_teacher/data/models/subject.dart';
import 'package:eschool_teacher/data/repositories/announcementRepository.dart';
import 'package:eschool_teacher/data/repositories/lessonRepository.dart';
import 'package:eschool_teacher/ui/widgets/announcementsContainer.dart';
import 'package:eschool_teacher/ui/widgets/appBarSubTitleContainer.dart';
import 'package:eschool_teacher/ui/widgets/appBarTitleContainer.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/ui/widgets/customTabBarContainer.dart';
import 'package:eschool_teacher/ui/widgets/lessonsContainer.dart';
import 'package:eschool_teacher/ui/widgets/tabBarBackgroundContainer.dart';
import 'package:eschool_teacher/utils/assets.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class SubjectScreen extends StatefulWidget {
  const SubjectScreen({
    required this.subject,
    required this.classSectionDetails,
    super.key,
  });
  final Subject subject;
  final ClassSectionDetails classSectionDetails;

  @override
  State<SubjectScreen> createState() => _SubjectScreenState();

  static Route<dynamic> route(RouteSettings routeSettings) {
    final arguments = routeSettings.arguments! as SubjectScreenArguments;
    return AdaptivePageRoute(
      builder: (_) => MultiBlocProvider(
        providers: [
          BlocProvider(create: (context) => LessonsCubit(LessonRepository())),
          BlocProvider(
            create: (context) => AnnouncementsCubit(AnnouncementRepository()),
          ),
        ],
        child: SubjectScreen(
          classSectionDetails: arguments.classSectionDetails,
          subject: arguments.subject,
        ),
      ),
    );
  }
}

class _SubjectScreenState extends State<SubjectScreen> {
  late String _selectedTabTitle = chaptersKey;

  late final ScrollController _scrollController = ScrollController()
    ..addListener(_announcementsScrollListener);

  @override
  void initState() {
    super.initState();
    Future.delayed(Duration.zero, () {
      if (context.mounted) {
        context.read<LessonsCubit>().fetchLessons(
          classSectionId: widget.classSectionDetails.id,
          subjectId: widget.subject.id,
          semesterId: widget.subject.semester?.id,
        );
        context.read<AnnouncementsCubit>().fetchAnnouncements(
          classSectionId: widget.classSectionDetails.id,
          subjectId: widget.subject.id,
          semesterId: widget.subject.semester?.id,
        );
      }
    });
  }

  void _announcementsScrollListener() {
    if (_scrollController.offset ==
        _scrollController.position.maxScrollExtent) {
      if (context.read<AnnouncementsCubit>().hasMore()) {
        context.read<AnnouncementsCubit>().fetchMoreAnnouncements(
          subjectId: widget.subject.id,
          semesterId: widget.subject.semester?.id,
          classSectionId: widget
              .classSectionDetails
              .id, //widget.classSectionDetails.classTeacherId
        );
      }
    }
  }

  @override
  void dispose() {
    _scrollController.removeListener(_announcementsScrollListener);
    _scrollController.dispose();
    super.dispose();
  }

  void _onTapFloatingActionAddButton() {
    Navigator.of(context)
        .pushNamed(
          _selectedTabTitle == chaptersKey
              ? Routes.addOrEditLesson
              : Routes.addOrEditAnnouncement,
          arguments: _selectedTabTitle == chaptersKey
              ? AddOrEditLessonArguments(
                  subject: widget.subject,
                  classSectionDetails: widget.classSectionDetails,
                )
              : AddOrEditAnnouncementArguments(
                  subject: widget.subject,
                  classSectionDetails: widget.classSectionDetails,
                ),
        )
        .then((value) {
          if (value != null && value is bool && value) {
            if (context.mounted) {
              //reload after adding new lesson or announcement
              if (_selectedTabTitle == chaptersKey) {
                context.read<LessonsCubit>().fetchLessons(
                  classSectionId: widget.classSectionDetails.id,
                  subjectId: widget.subject.id,
                  semesterId: widget.subject.semester?.id,
                );
              } else {
                context.read<AnnouncementsCubit>().fetchAnnouncements(
                  classSectionId: widget.classSectionDetails.id,
                  subjectId: widget.subject.id,
                  semesterId: widget.subject.semester?.id,
                );
              }
            }
          }
        });
  }

  Widget _buildAppBar() {
    return Align(
      alignment: Alignment.topCenter,
      child: ScreenTopBackgroundContainer(
        child: LayoutBuilder(
          builder: (context, boxConstraints) {
            return Stack(
              children: [
                Align(
                  alignment: AlignmentDirectional.topStart,
                  child: Padding(
                    padding: EdgeInsets.only(
                      left: UiUtils.screenContentHorizontalPadding,
                    ),
                    child: SvgButton(
                      onTap: () {
                        Navigator.pop(context);
                      },
                      svgIconUrl: Assets.backIcon,
                    ),
                  ),
                ),
                AppBarTitleContainer(
                  boxConstraints: boxConstraints,
                  title: widget.subject.subjectNameWithTypeAndSemester,
                ),
                AppBarSubTitleContainer(
                  boxConstraints: boxConstraints,
                  subTitle:
                      '${UiUtils.getTranslatedLabel(context, classKey)} ${widget.classSectionDetails.getClassSectionNameWithMedium()}',
                ),
                AnimatedAlign(
                  curve: UiUtils.tabBackgroundContainerAnimationCurve,
                  duration: UiUtils.tabBackgroundContainerAnimationDuration,
                  alignment: _selectedTabTitle == chaptersKey
                      ? AlignmentDirectional.centerStart
                      : AlignmentDirectional.centerEnd,
                  child: TabBackgroundContainer(boxConstraints: boxConstraints),
                ),
                CustomTabBarContainer(
                  boxConstraints: boxConstraints,
                  alignment: AlignmentDirectional.centerStart,
                  isSelected: _selectedTabTitle == chaptersKey,
                  onTap: () {
                    setState(() {
                      _selectedTabTitle = chaptersKey;
                    });
                  },
                  titleKey: chaptersKey,
                ),
                CustomTabBarContainer(
                  boxConstraints: boxConstraints,
                  alignment: AlignmentDirectional.centerEnd,
                  isSelected: _selectedTabTitle == announcementKey,
                  onTap: () {
                    setState(() {
                      _selectedTabTitle = announcementKey;
                    });
                  },
                  titleKey: announcementKey,
                ),
              ],
            );
          },
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      floatingActionButton: FloatingActionAddButton(
        onTap: _onTapFloatingActionAddButton,
      ),
      body: Stack(
        children: [
          Align(
            alignment: Alignment.topCenter,
            child: CustomRefreshIndicator(
              onRefreshCallback: () {
                if (_selectedTabTitle == chaptersKey) {
                  context.read<LessonsCubit>().fetchLessons(
                    classSectionId: widget.classSectionDetails.id,
                    subjectId: widget.subject.id,
                    semesterId: widget.subject.semester?.id,
                  );
                } else {
                  context.read<AnnouncementsCubit>().fetchAnnouncements(
                    classSectionId: widget.classSectionDetails.id,
                    subjectId: widget.subject.id,
                    semesterId: widget.subject.semester?.id,
                  );
                }
              },
              displacement: UiUtils.getScrollViewTopPadding(
                context: context,
                appBarHeightPercentage: UiUtils.appBarBiggerHeightPercentage,
              ),
              child: ListView(
                padding: EdgeInsets.only(
                  bottom: UiUtils.getScrollViewBottomPadding(context),
                  top: UiUtils.getScrollViewTopPadding(
                    context: context,
                    appBarHeightPercentage:
                        UiUtils.appBarBiggerHeightPercentage,
                  ),
                ),
                children: [
                  if (_selectedTabTitle == chaptersKey)
                    LessonsContainer(
                      classSectionDetails: widget.classSectionDetails,
                      subject: widget.subject,
                    )
                  else
                    AnnouncementsContainer(
                      classSectionDetails: widget.classSectionDetails,
                      subject: widget.subject,
                    ),
                ],
              ),
            ),
          ),
          _buildAppBar(),
        ],
      ),
    );
  }
}
