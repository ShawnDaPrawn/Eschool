import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/cubits/appConfigurationCubit.dart';
import 'package:eschool_teacher/cubits/classAttendanceCubit.dart';
import 'package:eschool_teacher/cubits/submitClassAttendanceCubit.dart';
import 'package:eschool_teacher/data/models/student.dart';
import 'package:eschool_teacher/data/repositories/teacherRepository.dart';
import 'package:eschool_teacher/data/models/attendanceEnums.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/ui/widgets/searchButton.dart';
import 'package:eschool_teacher/ui/widgets/studentAttendanceTileContainer.dart';
import 'package:eschool_teacher/utils/assets.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_svg/svg.dart';

class AttendanceScreen extends StatefulWidget {
  const AttendanceScreen({required this.students, super.key});
  final List<Student> students;

  @override
  State<AttendanceScreen> createState() => _AttendanceScreenState();

  static Route route(RouteSettings routeSettings) {
    return CupertinoPageRoute(
      builder: (_) => MultiBlocProvider(
        providers: [
          BlocProvider(
            create: (context) => ClassAttendanceCubit(TeacherRepository()),
          ),
          BlocProvider(
            create: (context) =>
                SubmitClassAttendanceCubit(TeacherRepository()),
          ),
        ],
        child: AttendanceScreen(
          students: routeSettings.arguments! as List<Student>,
        ),
      ),
    );
  }
}

class _AttendanceScreenState extends State<AttendanceScreen> {
  late List<Map<int, AttendanceType?>> _listOfAttendance = [];

  late DateTime _selectedAttendanceDate = DateTime.now();

  @override
  void initState() {
    super.initState();
    Future.delayed(Duration.zero, () {
      fetchClassAttendanceReports();
    });
  }

  void fetchClassAttendanceReports() {
    context.read<ClassAttendanceCubit>().fetchAttendanceReports(
      classSectionId: widget.students.first.classSectionId,
      date: _selectedAttendanceDate,
    );
  }

  void _updateAttendance(int studentId, AttendanceType? newType) {
    if (context.read<SubmitClassAttendanceCubit>().state
        is SubmitClassAttendanceInProgress) {
      return;
    }
    final index = _listOfAttendance.indexWhere(
      (element) => element.keys.first == studentId,
    );
    _listOfAttendance[index][studentId] = newType;
    setState(() {});
  }

  String _buildAttendanceDate() {
    return "${_selectedAttendanceDate.day.toString().padLeft(2, '0')}-${_selectedAttendanceDate.month.toString().padLeft(2, '0')}-${_selectedAttendanceDate.year}";
  }

  Future<void> _changeAttendanceDate() async {
    final pickedDate = await showDatePicker(
      lastDate: DateTime.now(),
      context: context,
      initialDate: _selectedAttendanceDate,
      firstDate: context
          .read<AppConfigurationCubit>()
          .getAppConfiguration()
          .sessionYear
          .startDate,
    );

    if (pickedDate != null) {
      //If teacher change the date then fetch attendance for that day
      setState(() {
        _selectedAttendanceDate = pickedDate;
        _listOfAttendance = [];
      });

      fetchClassAttendanceReports();
    }
  }

  Widget _buildAppbar() {
    return ScreenTopBackgroundContainer(
      padding: const EdgeInsets.all(0),
      heightPercentage: UiUtils.appBarSmallerHeightPercentage,
      child: LayoutBuilder(
        builder: (context, boxConstraints) {
          return Stack(
            children: [
              CustomBackButton(
                onTap: () {
                  if (context.read<SubmitClassAttendanceCubit>().state
                      is SubmitClassAttendanceInProgress) {
                    return;
                  }
                  Navigator.of(context).pop();
                },
                alignmentDirectional: AlignmentDirectional.centerStart,
              ),
              Align(
                alignment: AlignmentDirectional.centerEnd,
                child: BlocBuilder<ClassAttendanceCubit, ClassAttendanceState>(
                  builder: (context, state) {
                    if (state is ClassAttendanceFetchSuccess) {
                      if (state.isHoliday) {
                        return const SizedBox();
                      }
                      return SearchButton(
                        onTap: () {
                          if (context.read<SubmitClassAttendanceCubit>().state
                              is SubmitClassAttendanceInProgress) {
                            return;
                          }
                          Navigator.of(context)
                              .pushNamed<List<Map<int, AttendanceType?>>?>(
                                Routes.searchStudent,
                                arguments: {
                                  'fromAttendanceScreen': true,
                                  'students': widget.students,
                                  'listOfAttendanceReport': _listOfAttendance,
                                  'onLeaveStudents': state.leaveDetails,
                                },
                              )
                              .then((value) {
                                if (value != null) {
                                  _listOfAttendance = value;
                                  setState(() {});
                                }
                              });
                        },
                      );
                    }

                    return const SizedBox();
                  },
                ),
              ),
              Align(
                child: Container(
                  alignment: Alignment.center,
                  width: boxConstraints.maxWidth * 0.6,
                  child: Text(
                    UiUtils.getTranslatedLabel(context, takeAttendanceKey),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: UiUtils.screenTitleFontSize,
                      color: Theme.of(context).colorScheme.onInverseSurface,
                    ),
                  ),
                ),
              ),
              Align(
                child: Container(
                  alignment: Alignment.center,
                  width: boxConstraints.maxWidth * 0.6,
                  margin: EdgeInsets.only(
                    top:
                        boxConstraints.maxHeight * 0.25 +
                        UiUtils.screenTitleFontSize,
                  ),
                  child: GestureDetector(
                    onTap: () {
                      _changeAttendanceDate();
                    },
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Transform.translate(
                          offset: const Offset(0, -0.75),
                          child: Icon(
                            Icons.calendar_month,
                            size: 14,
                            color: Theme.of(
                              context,
                            ).colorScheme.onInverseSurface,
                          ),
                        ),
                        SizedBox(width: boxConstraints.maxWidth * 0.015),
                        Text(
                          _buildAttendanceDate(),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            fontSize: UiUtils.screenSubTitleFontSize,
                            color: Theme.of(
                              context,
                            ).colorScheme.onInverseSurface,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _buildSubmitAttendanceButton() {
    return BlocBuilder<ClassAttendanceCubit, ClassAttendanceState>(
      builder: (context, state) {
        if (state is ClassAttendanceFetchSuccess) {
          if (state.isHoliday) {
            return const SizedBox();
          }
          return BlocConsumer<
            SubmitClassAttendanceCubit,
            SubmitClassAttendanceState
          >(
            listener: (context, submitAttendanceState) {
              if (submitAttendanceState is SubmitClassAttendanceSuccess) {
                UiUtils.showBottomToastOverlay(
                  context: context,
                  errorMessage: UiUtils.getTranslatedLabel(
                    context,
                    attendanceSubmittedSuccessfullyKey,
                  ),
                  backgroundColor: StatusColors.of(context).success,
                );
              } else if (submitAttendanceState
                  is SubmitClassAttendanceFailure) {
                UiUtils.showBottomToastOverlay(
                  context: context,
                  errorMessage: UiUtils.getErrorMessageFromErrorCode(
                    context,
                    submitAttendanceState.exception,
                  ),
                  backgroundColor: Theme.of(context).colorScheme.error,
                );
              }
            },
            builder: (context, submitAttendanceState) {
              return Align(
                alignment: Alignment.bottomCenter,
                child: Padding(
                  padding: const EdgeInsets.only(bottom: 25),
                  child: CustomRoundedButton(
                    onTap: () {
                      if (submitAttendanceState
                          is SubmitClassAttendanceInProgress) {
                        return;
                      }
                      context
                          .read<SubmitClassAttendanceCubit>()
                          .submitAttendance(
                            dateTime: _selectedAttendanceDate,
                            classSectionId:
                                widget.students.first.classSectionId,
                            attendanceReport: _listOfAttendance,
                          );
                    },
                    elevation: 10,
                    height: UiUtils.bottomSheetButtonHeight,
                    widthPercentage: UiUtils.bottomSheetButtonWidthPercentage,
                    backgroundColor: Theme.of(context).colorScheme.primary,
                    buttonTitle: UiUtils.getTranslatedLabel(context, submitKey),
                    showBorder: false,
                    child:
                        submitAttendanceState is SubmitClassAttendanceInProgress
                        ? const CustomCircularProgressIndicator(
                            strokeWidth: 2,
                            widthAndHeight: 20,
                          )
                        : null,
                  ),
                ),
              );
            },
          );
        }
        return const SizedBox();
      },
    );
  }

  Widget _buildStudents() {
    return Align(
      alignment: Alignment.topCenter,
      child: SingleChildScrollView(
        padding: EdgeInsets.only(
          bottom: UiUtils.bottomSheetButtonHeight + 40.0,
          top: UiUtils.getScrollViewTopPadding(
            context: context,
            appBarHeightPercentage: UiUtils.appBarSmallerHeightPercentage,
          ),
        ),
        clipBehavior: Clip.none,
        child: BlocConsumer<ClassAttendanceCubit, ClassAttendanceState>(
          listener: (context, state) {
            if (state is ClassAttendanceFetchSuccess) {
              //
              if (!state.isHoliday) {
                //
                AttendanceType? getDefaultAttendance(int studentId) {
                  if (state.isOnLeave(studentId)) {
                    final leaveType = state.getLeaveType(studentId);
                    if (leaveType == AttendanceLeaveType.full)
                      return AttendanceType.absent;
                    if (leaveType == AttendanceLeaveType.firstHalf)
                      return AttendanceType.firstHalfAbsent;
                    if (leaveType == AttendanceLeaveType.secondHalf)
                      return AttendanceType.secondHalfAbsent;
                    return null;
                  }
                  return null; // Present by default, but let's leave it null to signify "nothing filled yet". Wait, user said "so we show present by default."
                  // If we want it to show present by default in the dropdown, the Dropdown will handle `null` by selecting `AttendanceType.present` visually, but we can store it as `null`.
                }

                if (state.attendanceReports.isNotEmpty) {
                  //
                  for (final student in widget.students) {
                    //Find the attendance report and update the list of attendace
                    try {
                      final attendanceReport = state.attendanceReports
                          .where((element) => element.studentId == student.id)
                          .first;

                      _listOfAttendance.add({
                        student.id:
                            attendanceReport.attendanceType ??
                            getDefaultAttendance(student.id),
                      });
                    } catch (_) {
                      _listOfAttendance.add({
                        student.id: getDefaultAttendance(student.id),
                      });
                    }
                  }
                } else {
                  //If passed date is not holiday and also attendance reports is empty
                  //means attendance of that day not taken
                  for (final student in widget.students) {
                    _listOfAttendance.add({
                      student.id: getDefaultAttendance(student.id),
                    });
                  }
                }
                setState(() {});
              }
            }
          },
          builder: (context, state) {
            if (state is ClassAttendanceFetchSuccess) {
              if (state.isHoliday) {
                return Padding(
                  padding: const EdgeInsets.only(left: 10, right: 10),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      SizedBox(
                        height: MediaQuery.sizeOf(context).height * 0.025,
                      ),
                      SizedBox(
                        height: MediaQuery.sizeOf(context).height * 0.35,
                        child: SvgPicture.asset(Assets.fileNotFoundImage),
                      ),
                      SizedBox(
                        height: MediaQuery.sizeOf(context).height * 0.025,
                      ),
                      Text(
                        '${UiUtils.getTranslatedLabel(context, holidayKey)} : ${state.holidayDetails.title}',
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      const SizedBox(height: 10),
                      Text(
                        UiUtils.getTranslatedLabel(
                          context,
                          attendanceNotViewEditKey,
                        ),
                        textAlign: TextAlign.center,
                      ),
                    ],
                  ),
                );
              }

              if (_listOfAttendance.isEmpty) {
                return const SizedBox();
              }

              return Column(
                children: widget.students.map((student) {
                  final attendanceType = _listOfAttendance
                      .where((element) => element.keys.first == student.id)
                      .toList()
                      .first[student.id];
                  return StudentAttendanceTileContainer(
                    attendanceType: attendanceType,
                    student: student,
                    updateAttendance: _updateAttendance,
                    isOnLeave: state.isOnLeave(student.id),
                    leaveType: state.getLeaveType(student.id),
                  );
                }).toList(),
              );
            }

            if (state is ClassAttendanceFetchFailure) {
              return ErrorContainer(
                errorMessageCode: state.errorMessage,
                onTapRetry: () {
                  fetchClassAttendanceReports();
                },
              );
            }
            return Padding(
              padding: EdgeInsets.only(
                top: MediaQuery.sizeOf(context).height * 0.25,
              ),
              child: CustomCircularProgressIndicator(
                indicatorColor: Theme.of(context).colorScheme.primary,
              ),
            );
          },
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, dynamic result) {
        if (context.read<SubmitClassAttendanceCubit>().state
            is! SubmitClassAttendanceInProgress) {
          if (didPop) {
            return;
          }
          Navigator.of(context).pop();
        }
      },
      child: Scaffold(
        body: Stack(
          children: [
            _buildStudents(),
            _buildAppbar(),
            _buildSubmitAttendanceButton(),
          ],
        ),
      ),
    );
  }
}
