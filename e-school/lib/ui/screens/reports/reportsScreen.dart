import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/data/models/subject.dart';
import 'package:eschool/cubits/appConfigurationCubit.dart';
import 'package:eschool/ui/widgets/customBackButton.dart';
import 'package:eschool/ui/widgets/screenTopBackgroundContainer.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:eschool/cubits/sessionYearCubit.dart';
import 'package:eschool/data/repositories/systemInfoRepository.dart';
import 'package:eschool/ui/widgets/errorContainer.dart';
import 'package:eschool/ui/screens/reports/widgets/reportsTabContainer.dart';
import 'package:eschool/ui/screens/reports/tabs/attendanceReportTab.dart';
import 'package:eschool/ui/screens/reports/tabs/feesReportTab.dart';
import 'package:eschool/ui/screens/reports/tabs/examReportTab.dart';
import 'package:eschool/data/repositories/reportRepository.dart';
import 'package:eschool/data/repositories/studentRepository.dart';
import 'package:eschool/ui/screens/reports/cubits/attendanceReportCubit.dart';
import 'package:eschool/ui/screens/reports/cubits/feesReportCubit.dart';
import 'package:eschool/ui/screens/reports/cubits/examReportCubit.dart';
import 'package:eschool/cubits/resultsCubit.dart';
import 'package:eschool/cubits/resultsOnlineCubit.dart';
import 'package:eschool/ui/screens/reports/tabs/assignmentReportTab.dart';
import 'package:eschool/ui/screens/reports/cubits/assignmentReportCubit.dart';
import 'package:eschool/cubits/assignmentsCubit.dart';
import 'package:eschool/data/repositories/assignmentRepository.dart';
import 'package:eschool/data/repositories/resultRepository.dart';
import 'package:eschool/cubits/feesTransactionCubit.dart';
import 'package:eschool/ui/screens/reports/tabs/leaveReportTab.dart';
import 'package:eschool/ui/screens/reports/cubits/leaveReportCubit.dart';
import 'package:eschool/cubits/leavesCubit.dart';
import 'package:eschool/data/repositories/leaveRepository.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class ReportsScreen extends StatefulWidget {
  const ReportsScreen({super.key, this.childId, this.subjects});
  final int? childId;
  final List<Subject>? subjects;

  @override
  ReportsScreenState createState() => ReportsScreenState();

  static Widget builder(
    BuildContext context, {
    int? childId,
    List<Subject>? subjects,
  }) {
    return MultiBlocProvider(
      providers: [
        BlocProvider<AttendanceReportCubit>(
          create: (context) => AttendanceReportCubit(ReportRepository()),
        ),
        BlocProvider<FeesReportCubit>(
          create: (context) => FeesReportCubit(ReportRepository()),
        ),
        BlocProvider<FeesTransactionsCubit>(
          create: (context) => FeesTransactionsCubit(StudentRepository()),
        ),
        BlocProvider<ExamReportCubit>(
          create: (context) => ExamReportCubit(ReportRepository()),
        ),
        BlocProvider<ResultsCubit>(
          create: (context) => ResultsCubit(StudentRepository()),
        ),
        BlocProvider<ResultsOnlineCubit>(
          create: (context) => ResultsOnlineCubit(ResultOnlineRepository()),
        ),
        BlocProvider<SessionYearCubit>(
          create: (context) =>
              SessionYearCubit(SystemRepository())..fetchSessionYears(),
        ),
        BlocProvider<AssignmentReportCubit>(
          create: (context) => AssignmentReportCubit(ReportRepository()),
        ),
        BlocProvider<AssignmentsCubit>(
          create: (context) => AssignmentsCubit(AssignmentRepository()),
        ),
        BlocProvider<LeaveReportCubit>(
          create: (context) => LeaveReportCubit(ReportRepository()),
        ),
        BlocProvider<LeaveCubit>(
          create: (context) => LeaveCubit(LeaveRepository()),
        ),
      ],
      child: ReportsScreen(childId: childId, subjects: subjects),
    );
  }

  static Route route(RouteSettings routeSettings) {
    final arguments = routeSettings.arguments as Map<String, dynamic>?;
    return CupertinoPageRoute(
      builder: (context) => ReportsScreen.builder(
        context,
        childId: arguments?['childId'],
        subjects: arguments?['subjects'],
      ),
    );
  }
}

class ReportsScreenState extends State<ReportsScreen> {
  int _currentSelectedTab = 0;
  late final PageController _pageController = PageController();

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  List<String> _getTabs(BuildContext context) {
    List<String> tabs = [UiUtils.getTranslatedLabel(context, attendanceKey)];

    final isStudent = !context.read<AuthCubit>().isParent();
    final canPayFees = context
        .read<AppConfigurationCubit>()
        .getAppConfiguration()
        .canStudentPayTheirFees;

    if (!isStudent || canPayFees) {
      tabs.add(UiUtils.getTranslatedLabel(context, feesKey));
    }

    tabs.addAll([
      UiUtils.getTranslatedLabel(context, examsKey),
      UiUtils.getTranslatedLabel(context, assignmentsKey),
      UiUtils.getTranslatedLabel(context, leavesKey),
    ]);

    return tabs;
  }

  Widget _buildAppBar() {
    return ScreenTopBackgroundContainer(
      heightPercentage: UiUtils.appBarSmallerHeightPercentage,
      child: Stack(
        children: [
          context.read<AuthCubit>().isParent()
              ? const CustomBackButton()
              : const SizedBox.shrink(),
          Align(
            alignment: Alignment.topCenter,
            child: Text(
              UiUtils.getTranslatedLabel(context, reportsKey),
              style: TextStyle(
                color: Theme.of(context).colorScheme.onPrimary,
                fontSize: UiUtils.screenTitleFontSize,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBody(List<String> tabs) {
    return BlocBuilder<SessionYearCubit, SessionYearState>(
      builder: (context, state) {
        if (state is SessionYearFetchInProgress ||
            state is SessionYearInitial) {
          return Stack(
            children: [
              Column(
                children: [
                  _buildTabBar(tabs),
                  Expanded(child: _buildShimmerContent(tabs)),
                ],
              ),
              Align(alignment: Alignment.topCenter, child: _buildAppBar()),
            ],
          );
        }
        if (state is SessionYearFetchFailure) {
          return Stack(
            children: [
              Center(
                child: ErrorContainer(
                  errorMessageCode: state.errorMessage,
                  onTapRetry: () {
                    context.read<SessionYearCubit>().fetchSessionYears();
                  },
                ),
              ),
              Align(alignment: Alignment.topCenter, child: _buildAppBar()),
            ],
          );
        }
        return Stack(
          children: [
            Column(
              children: [
                _buildTabBar(tabs),
                Expanded(child: _buildTabs(tabs)),
              ],
            ),
            Align(alignment: Alignment.topCenter, child: _buildAppBar()),
          ],
        );
      },
    );
  }

  Widget _buildShimmerContent(List<String> tabs) {
    if (tabs.isEmpty) return const SizedBox.shrink();

    final tabName = tabs[_currentSelectedTab];
    if (tabName == UiUtils.getTranslatedLabel(context, attendanceKey)) {
      return AttendanceReportTab.buildShimmer(context);
    } else if (tabName == UiUtils.getTranslatedLabel(context, feesKey)) {
      return FeesReportTab.buildShimmer(context);
    } else if (tabName == UiUtils.getTranslatedLabel(context, examsKey)) {
      return ExamReportTab.buildShimmer(context, 0);
    } else if (tabName == UiUtils.getTranslatedLabel(context, assignmentsKey)) {
      return AssignmentReportTab.buildShimmer(context);
    } else if (tabName == UiUtils.getTranslatedLabel(context, leavesKey)) {
      return LeaveReportTab.buildShimmer(context);
    }

    return const SizedBox.shrink();
  }

  Widget _buildTabs(List<String> tabs) {
    return PageView.builder(
      controller: _pageController,
      onPageChanged: (index) {
        setState(() {
          _currentSelectedTab = index;
        });
      },
      itemCount: tabs.length,
      itemBuilder: (context, index) {
        final tabName = tabs[index];
        if (tabName == UiUtils.getTranslatedLabel(context, attendanceKey)) {
          return AttendanceReportTab(childId: widget.childId);
        } else if (tabName == UiUtils.getTranslatedLabel(context, feesKey)) {
          return FeesReportTab(childId: widget.childId);
        } else if (tabName == UiUtils.getTranslatedLabel(context, examsKey)) {
          return ExamReportTab(
            childId: widget.childId,
            subjects: widget.subjects,
          );
        } else if (tabName ==
            UiUtils.getTranslatedLabel(context, assignmentsKey)) {
          return AssignmentReportTab(
            childId: widget.childId,
            subjects: widget.subjects,
          );
        } else if (tabName == UiUtils.getTranslatedLabel(context, leavesKey)) {
          return LeaveReportTab(childId: widget.childId);
        }
        return SizedBox.shrink();
      },
    );
  }

  Widget _buildTabBar(List<String> tabs) {
    return Padding(
      padding: EdgeInsets.only(
        top: UiUtils.getScrollViewTopPadding(
          context: context,
          appBarHeightPercentage: UiUtils.appBarSmallerHeightPercentage,
        ),
        bottom: 10,
      ),
      child: ReportsTabContainer(
        tabs: tabs,
        onTapTab: (index) {
          setState(() {
            _currentSelectedTab = index;
          });
          _pageController.animateToPage(
            index,
            duration: const Duration(milliseconds: 300),
            curve: Curves.easeInOut,
          );
        },
        selectedTabIndex: _currentSelectedTab,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final tabs = _getTabs(context);
    return Scaffold(
      backgroundColor: Theme.of(context).colorScheme.surface,
      body: _buildBody(tabs),
    );
  }
}
