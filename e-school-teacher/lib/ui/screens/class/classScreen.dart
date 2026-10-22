import 'package:eschool_teacher/app/routeArguments.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/cubits/studentsByClassSectionCubit.dart';
import 'package:eschool_teacher/cubits/subjectsOfClassSectionCubit.dart';
import 'package:eschool_teacher/cubits/appConfigurationCubit.dart';
import 'package:eschool_teacher/data/models/classSectionDetails.dart';
import 'package:eschool_teacher/data/repositories/studentRepository.dart';
import 'package:eschool_teacher/data/repositories/teacherRepository.dart';
import 'package:eschool_teacher/ui/screens/class/widgets/studentsContainer.dart';
import 'package:eschool_teacher/ui/screens/class/widgets/subjectsContainer.dart';
import 'package:eschool_teacher/ui/widgets/appBarTitleContainer.dart';
import 'package:eschool_teacher/ui/widgets/customAppbar.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/ui/widgets/customTabBarContainer.dart';
import 'package:eschool_teacher/ui/widgets/searchButton.dart';
import 'package:eschool_teacher/ui/widgets/tabBarBackgroundContainer.dart';
import 'package:eschool_teacher/utils/assets.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_svg/svg.dart';

class ClassScreen extends StatefulWidget {
  const ClassScreen({
    required this.isClassTeacher,
    required this.classSection,
    super.key,
  });
  final bool isClassTeacher;
  final ClassSectionDetails classSection;

  @override
  State<ClassScreen> createState() => _ClassScreenState();

  static Route route(RouteSettings routeSettings) {
    final arguments = routeSettings.arguments! as ClassScreenArguments;
    return CupertinoPageRoute(
      builder: (_) => MultiBlocProvider(
        providers: [
          BlocProvider(
            create: (context) =>
                SubjectsOfClassSectionCubit(TeacherRepository()),
          ),
          BlocProvider(
            create: (context) =>
                StudentsByClassSectionCubit(StudentRepository()),
          ),
        ],
        child: ClassScreen(
          classSection: arguments.classSection,
          isClassTeacher: arguments.isClassTeacher,
        ),
      ),
    );
  }
}

class _ClassScreenState extends State<ClassScreen> {
  @override
  void initState() {
    Future.delayed(Duration.zero, () {
      if (context.mounted) {
        context.read<SubjectsOfClassSectionCubit>().fetchSubjects(
          widget.classSection.id,
        );
        if (widget.isClassTeacher) {
          context.read<StudentsByClassSectionCubit>().fetchStudents(
            classSectionId: widget.classSection.id,
            subjectId: null,
            semesterId: context
                .read<AppConfigurationCubit>()
                .getAppConfiguration()
                .currentSemester
                ?.id,
          );
        }
      }
    });
    super.initState();
  }

  late String _selectedTabTitle = studentsKey;

  Widget _buildAppbar() {
    return widget.isClassTeacher
        ? Align(
            alignment: Alignment.topCenter,
            child: ScreenTopBackgroundContainer(
              child: LayoutBuilder(
                builder: (context, boxConstraints) {
                  return Stack(
                    children: [
                      const CustomBackButton(),
                      if (_selectedTabTitle == subjectsKey)
                        const SizedBox()
                      else
                        Align(
                          alignment: AlignmentDirectional.topEnd,
                          child:
                              BlocBuilder<
                                StudentsByClassSectionCubit,
                                StudentsByClassSectionState
                              >(
                                builder: (context, state) {
                                  if (state
                                          is StudentsByClassSectionFetchSuccess &&
                                      state.students.isNotEmpty) {
                                    return SearchButton(
                                      onTap: () {
                                        Navigator.of(context).pushNamed(
                                          Routes.searchStudent,
                                          arguments: SearchStudentArguments(
                                            students: context
                                                .read<
                                                  StudentsByClassSectionCubit
                                                >()
                                                .getStudents(),
                                            fromAttendanceScreen: false,
                                          ),
                                        );
                                      },
                                    );
                                  }
                                  return const SizedBox();
                                },
                              ),
                        ),
                      AppBarTitleContainer(
                        boxConstraints: boxConstraints,
                        title:
                            '${UiUtils.getTranslatedLabel(context, classKey)} ${widget.classSection.getClassSectionNameWithMediumAndSemester(context: context)}',
                      ),
                      AnimatedAlign(
                        curve: UiUtils.tabBackgroundContainerAnimationCurve,
                        duration:
                            UiUtils.tabBackgroundContainerAnimationDuration,
                        alignment: _selectedTabTitle == studentsKey
                            ? AlignmentDirectional.centerStart
                            : AlignmentDirectional.centerEnd,
                        child: TabBackgroundContainer(
                          boxConstraints: boxConstraints,
                        ),
                      ),
                      CustomTabBarContainer(
                        boxConstraints: boxConstraints,
                        alignment: AlignmentDirectional.centerStart,
                        isSelected: _selectedTabTitle == studentsKey,
                        onTap: () {
                          setState(() {
                            _selectedTabTitle = studentsKey;
                          });
                        },
                        titleKey: studentsKey,
                      ),
                      CustomTabBarContainer(
                        boxConstraints: boxConstraints,
                        alignment: AlignmentDirectional.centerEnd,
                        isSelected: _selectedTabTitle == subjectsKey,
                        onTap: () {
                          setState(() {
                            _selectedTabTitle = subjectsKey;
                          });
                        },
                        titleKey: subjectsKey,
                      ),
                    ],
                  );
                },
              ),
            ),
          )
        : Align(
            alignment: Alignment.topCenter,
            child: CustomAppBar(
              subTitle: UiUtils.getTranslatedLabel(context, subjectsKey),
              title:
                  '${UiUtils.getTranslatedLabel(context, classKey)} ${widget.classSection.getClassSectionNameWithMediumAndSemester(context: context)}',
            ),
          );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      floatingActionButton:
          widget.isClassTeacher && _selectedTabTitle == studentsKey
          ? BlocBuilder<
              StudentsByClassSectionCubit,
              StudentsByClassSectionState
            >(
              builder: (context, state) {
                if (state is StudentsByClassSectionFetchSuccess &&
                    state.students.isNotEmpty) {
                  return FloatingActionButton(
                    onPressed: () {
                      Navigator.of(
                        context,
                      ).pushNamed(Routes.attendance, arguments: state.students);
                    },
                    backgroundColor: Theme.of(context).colorScheme.primary,
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: SvgPicture.asset(
                        Assets.takeAttendance,
                        colorFilter: ColorFilter.mode(
                          Theme.of(context).colorScheme.onPrimary,
                          BlendMode.srcIn,
                        ),
                      ),
                    ),
                  );
                }

                return const SizedBox();
              },
            )
          : const SizedBox(),
      body: Stack(
        children: [
          Align(
            alignment: Alignment.topCenter,
            child: SingleChildScrollView(
              padding: EdgeInsets.only(
                left: MediaQuery.sizeOf(context).width * 0.075,
                right: MediaQuery.sizeOf(context).width * 0.075,
                top: UiUtils.getScrollViewTopPadding(
                  context: context,
                  appBarHeightPercentage: widget.isClassTeacher
                      ? UiUtils.appBarBiggerHeightPercentage
                      : UiUtils.appBarSmallerHeightPercentage,
                ),
              ),
              child: widget.isClassTeacher
                  ? _selectedTabTitle == subjectsKey
                        ? SubjectsContainer(
                            classSectionDetails: widget.classSection,
                          )
                        : StudentsContainer(
                            classSectionDetails: widget.classSection,
                          )
                  : SubjectsContainer(classSectionDetails: widget.classSection),
            ),
          ),
          _buildAppbar(),
        ],
      ),
    );
  }
}
