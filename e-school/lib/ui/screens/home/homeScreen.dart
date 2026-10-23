import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/cubits/academicCalendarCubit.dart';
import 'package:eschool/cubits/appConfigurationCubit.dart';
import 'package:eschool/cubits/assignmentsCubit.dart';
import 'package:eschool/cubits/attendanceCubit.dart';
import 'package:eschool/cubits/chat/chatUsersCubit.dart';
import 'package:eschool/cubits/eventsCubit.dart';
import 'package:eschool/cubits/resultsCubit.dart';
import 'package:eschool/cubits/semestersCubit.dart';
import 'package:eschool/cubits/studentParentDetailsCubit.dart';
import 'package:eschool/cubits/timeTableCubit.dart';
import 'package:eschool/data/repositories/assignmentRepository.dart';
import 'package:eschool/data/repositories/settingsRepository.dart';
import 'package:eschool/data/repositories/studentRepository.dart';
import 'package:eschool/data/repositories/systemInfoRepository.dart';
import 'package:eschool/ui/screens/academicCalendar/academicCalendarScreen.dart';
import 'package:eschool/ui/screens/chat/chatUsersScreen.dart';
import 'package:eschool/ui/screens/home/cubits/assignmentsTabSelectionCubit.dart';
import 'package:eschool/ui/screens/home/widgets/bottomNavigationItemContainer.dart';
import 'package:eschool/ui/screens/home/widgets/examContainer.dart';
import 'package:eschool/ui/screens/home/widgets/homeContainer.dart';
import 'package:eschool/ui/screens/home/widgets/moreMenuBottomsheetContainer.dart';
import 'package:eschool/ui/screens/home/widgets/parentProfileContainer.dart';
import 'package:eschool/ui/screens/reports/reportsScreen.dart';
import 'package:eschool/ui/widgets/appUnderMaintenanceContainer.dart';
import 'package:eschool/ui/widgets/assignmentsContainer.dart';
import 'package:eschool/ui/widgets/attendanceContainer.dart';
import 'package:eschool/ui/widgets/forceUpdateDialogContainer.dart';
import 'package:eschool/ui/widgets/noticeBoardContainer.dart';
import 'package:eschool/ui/widgets/resultsContainer.dart';
import 'package:eschool/ui/widgets/settingsContainer.dart';
import 'package:eschool/ui/widgets/timetableContainer.dart';
import 'package:eschool/utils/assets.dart';
import 'package:eschool/utils/constants.dart';
import 'package:eschool/utils/homeBottomsheetMenu.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool_notifications/eschool_notifications.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_svg/flutter_svg.dart';

//value notifier to show notification icon active/deactivated
/// The unread badge; kept by eschool_notifications.
ValueNotifier<int> get notificationCountValueNotifier =>
    NotificationUtility.unreadCount;

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});
  static GlobalKey<HomeScreenState> homeScreenKey =
      GlobalKey<HomeScreenState>();

  @override
  State<HomeScreen> createState() => HomeScreenState();

  static Route route(RouteSettings routeSettings) {
    return AdaptivePageRoute(
      contentColumn: false,
      builder: (_) => MultiBlocProvider(
        providers: [
          BlocProvider<TimeTableCubit>(
            create: (_) => TimeTableCubit(StudentRepository()),
          ),
          BlocProvider<StudentParentDetailsCubit>(
            create: (_) => StudentParentDetailsCubit(StudentRepository()),
          ),
          BlocProvider<AssignmentsCubit>(
            create: (_) => AssignmentsCubit(AssignmentRepository()),
          ),
          BlocProvider<AttendanceCubit>(
            create: (context) => AttendanceCubit(StudentRepository()),
          ),
          BlocProvider<EventsCubit>(
            create: (context) => EventsCubit(SystemRepository()),
          ),
          BlocProvider<AssignmentsTabSelectionCubit>(
            create: (_) => AssignmentsTabSelectionCubit(),
          ),
          BlocProvider<ResultsCubit>(
            create: (_) => ResultsCubit(StudentRepository()),
          ),
        ],
        child: HomeScreen(key: homeScreenKey),
      ),
    );
  }
}

class HomeScreenState extends State<HomeScreen>
    with TickerProviderStateMixin, WidgetsBindingObserver {
  late final AnimationController _animationController = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 500),
  );

  late final Animation<double> _bottomNavAndTopProfileAnimation =
      Tween<double>(begin: 0, end: 1).animate(
        CurvedAnimation(parent: _animationController, curve: Curves.easeInOut),
      );

  late final List<AnimationController> _bottomNavItemTitlesAnimationController =
      [];

  late final AnimationController _moreMenuBottomsheetAnimationController =
      AnimationController(
        vsync: this,
        duration: homeMenuBottomSheetAnimationDuration,
      );

  late final Animation<Offset> _moreMenuBottomsheetAnimation =
      Tween<Offset>(begin: const Offset(0, 1.3), end: Offset.zero).animate(
        CurvedAnimation(
          parent: _moreMenuBottomsheetAnimationController,
          curve: Curves.easeInOut,
        ),
      );

  late final Animation<double> _moreMenuBackgroundContainerColorAnimation =
      Tween<double>(begin: 0, end: 1).animate(
        CurvedAnimation(
          parent: _moreMenuBottomsheetAnimationController,
          curve: Curves.easeInOut,
        ),
      );

  //maintaining separate index for stack item, will be changed when item in bottom-sheet is tapped or bottom-navbar item is tapped (other then menu)
  late int _currentSelectedStackItemIndex = 0;

  late int _currentSelectedBottomNavIndex = 0;
  late int _previousSelectedBottomNavIndex = -1;

  //index of opened homeBottomsheet menu
  late int _currentlyOpenMenuIndex = -1;

  late bool _isMoreMenuOpen = false;

  final List<BottomNavItem> _bottomNavItems = [
    BottomNavItem(
      activeImageUrl: Assets.homeActiveIcon,
      disableImageUrl: Assets.homeIcon,
      title: homeKey,
    ),
    BottomNavItem(
      activeImageUrl: Assets.chatActiveIcon,
      disableImageUrl: Assets.chatIcon,
      title: chatKey,
    ),
    BottomNavItem(
      activeImageUrl: Assets.assignmentActiveIcon,
      disableImageUrl: Assets.assignmentIcon,
      title: assignmentsKey,
    ),
    BottomNavItem(
      activeImageUrl: Assets.menuActiveIcon,
      disableImageUrl: Assets.menuIcon,
      title: menuKey,
    ),
  ];

  late final List<GlobalKey> _bottomNavItemShowCaseKey = [];

  @override
  Future<void> didChangeAppLifecycleState(AppLifecycleState state) async {
    //Refreshing the shared preferences to get the latest notification count, it there were any notifications in the background
    if (state == AppLifecycleState.resumed) {
      notificationCountValueNotifier.value = await SettingsRepository()
          .getNotificationCount();
      final backgroundChatMessages = await SettingsRepository()
          .getBackgroundChatNotificationData();
      if (backgroundChatMessages.isNotEmpty) {
        //empty any old data and stream new once
        SettingsRepository().setBackgroundChatNotificationData(data: []);
        for (int i = 0; i < backgroundChatMessages.length; i++) {
          ChatNotificationsUtils.addChatStreamValue(
            chatData: backgroundChatMessages[i],
          );
        }
      }
    }
  }

  @override
  void initState() {
    super.initState();
    initAnimations();
    initShowCaseKeys();
    WidgetsBinding.instance.addObserver(this);

    _animationController.forward();
    Future.delayed(Duration.zero, () {
      //setup notification callback here
      NotificationUtility.setUpNotificationService(context);
      NotificationUtility.subscribeOrUnsubscribeToNotificationTopics(
        audience: NotificationAudience.students,
        isUnsubscribe: false,
      );
    });
  }

  void navigateToAssignmentContainer() {
    Navigator.of(context).popUntil((route) => route.isFirst);
    changeBottomNavItem(2);
  }

  void initAnimations() {
    for (var i = 0; i < _bottomNavItems.length; i++) {
      _bottomNavItemTitlesAnimationController.add(
        AnimationController(
          value: i == _currentSelectedBottomNavIndex ? 0.0 : 1.0,
          vsync: this,
          duration: const Duration(milliseconds: 400),
        ),
      );
    }
  }

  void initShowCaseKeys() {
    for (var i = 0; i < _bottomNavItems.length; i++) {
      _bottomNavItemShowCaseKey.add(GlobalKey());
    }
  }

  @override
  void dispose() {
    _animationController.dispose();
    WidgetsBinding.instance.removeObserver(this);
    for (final animationController in _bottomNavItemTitlesAnimationController) {
      animationController.dispose();
    }
    _moreMenuBottomsheetAnimationController.dispose();
    super.dispose();
  }

  Future<void> changeBottomNavItem(int index) async {
    if (_moreMenuBottomsheetAnimationController.isAnimating) {
      return;
    }
    _bottomNavItemTitlesAnimationController[_currentSelectedBottomNavIndex]
        .forward();

    //need to assign previous selected bottom index only if menu is close
    if (!_isMoreMenuOpen && _currentlyOpenMenuIndex == -1) {
      _previousSelectedBottomNavIndex = _currentSelectedBottomNavIndex;
    }

    //change current selected bottom index
    setState(() {
      _currentSelectedBottomNavIndex = index;

      //if user taps on non-last bottom nav item then change _currentlyOpenMenuIndex
      if (_currentSelectedBottomNavIndex != _bottomNavItems.length - 1) {
        _currentlyOpenMenuIndex = -1;
        //changing the stack item index only if the bottom-sheet is not opened (clicked on an item other then "Menu")
        _currentSelectedStackItemIndex = index;
      }
    });

    _bottomNavItemTitlesAnimationController[_currentSelectedBottomNavIndex]
        .reverse();

    //if bottom index is last means open/close the bottom sheet
    if (index == _bottomNavItems.length - 1) {
      if (_moreMenuBottomsheetAnimationController.isCompleted) {
        //close the menu
        await _moreMenuBottomsheetAnimationController.reverse();

        setState(() {
          _isMoreMenuOpen = !_isMoreMenuOpen;
        });

        //change bottom nav to previous selected index
        //only if there is not any opened menu item container
        if (_currentlyOpenMenuIndex == -1) {
          changeBottomNavItem(_previousSelectedBottomNavIndex);
        }
      } else {
        //open menu
        await _moreMenuBottomsheetAnimationController.forward();
        setState(() {
          _isMoreMenuOpen = !_isMoreMenuOpen;
        });
      }
    } else {
      //if current selected index is not last index(bottom nav item)
      //and menu is open then close the menu
      if (_moreMenuBottomsheetAnimationController.isCompleted) {
        await _moreMenuBottomsheetAnimationController.reverse();
        setState(() {
          _isMoreMenuOpen = !_isMoreMenuOpen;
        });
      }
    }
  }

  Future<void> _closeBottomMenu() async {
    if (_currentlyOpenMenuIndex == -1) {
      //close the menu and change bottom sheet
      changeBottomNavItem(_previousSelectedBottomNavIndex);
    } else {
      await _moreMenuBottomsheetAnimationController.reverse();
      setState(() {
        _isMoreMenuOpen = !_isMoreMenuOpen;
      });
    }
  }

  Future<void> _onTapMoreMenuItemContainer(int index) async {
    await _moreMenuBottomsheetAnimationController.reverse();
    _currentlyOpenMenuIndex = index;
    _isMoreMenuOpen = !_isMoreMenuOpen;
    //on any bottomsheet item tap, change stack index to last item
    _currentSelectedStackItemIndex = _bottomNavItems.length - 1;
    setState(() {});
  }

  Widget _buildBottomNavigationContainer() {
    return FadeTransition(
      opacity: _bottomNavAndTopProfileAnimation,
      child: SlideTransition(
        position: _bottomNavAndTopProfileAnimation.drive(
          Tween<Offset>(begin: const Offset(0, 1), end: Offset.zero),
        ),
        child: Container(
          alignment: Alignment.center,
          // padding: EdgeInsets.only(
          //   bottom: MediaQuery.sizeOf(context).height * (0.075) * (0.075),
          // ),
          margin: EdgeInsets.only(bottom: UiUtils.bottomNavigationBottomMargin),
          decoration: BoxDecoration(
            boxShadow: [
              BoxShadow(
                color: UiUtils.getColorScheme(context).shadow
                    .withValues(alpha: 0.15),
                offset: const Offset(2.5, 2.5),
                blurRadius: 20,
              ),
            ],
            color: Theme.of(context).colorScheme.surface,
            borderRadius: BorderRadius.circular(10),
          ),
          width: MediaQuery.sizeOf(context).width * 0.85,
          height:
              MediaQuery.sizeOf(context).height *
              UiUtils.bottomNavigationHeightPercentage,
          child: BlocBuilder<ChatUsersCubit, ChatUsersState>(
            builder: (context, state) {
              return LayoutBuilder(
                builder: (context, boxConstraints) {
                  return Row(
                    mainAxisAlignment: MainAxisAlignment.spaceAround,
                    children: _bottomNavItems
                        .where(
                          (item) =>
                              item.title != chatKey ||
                              allowsSchoolModule(
                                context.watch<MeContextCubit>().state.context,
                                'chat',
                              ),
                        )
                        .map((bottomNavItem) {
                          final int index = _bottomNavItems.indexWhere(
                            (e) => e.title == bottomNavItem.title,
                          );
                          return BottomNavItemContainer(
                            onTap: changeBottomNavItem,
                            boxConstraints: boxConstraints,
                            currentIndex: _currentSelectedBottomNavIndex,
                            bottomNavItem: _bottomNavItems[index],
                            animationController:
                                _bottomNavItemTitlesAnimationController[index],
                            index: index,
                            notificationCount:
                                (index == 1 && state is ChatUsersFetchSuccess)
                                ? state.totalUnreadUsers
                                : null,
                          );
                        })
                        .toList(),
                  );
                },
              );
            },
          ),
        ),
      ),
    );
  }

  Widget _buildMoreMenuBackgroundContainer() {
    return GestureDetector(
      onTap: () async {
        _closeBottomMenu();
      },
      child: Container(
        width: MediaQuery.sizeOf(context).width,
        height: MediaQuery.sizeOf(context).height,
        color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.75),
      ),
    );
  }

  //To load the selected menu item
  //it _currentlyOpenMenuIndex is 0 then load the container based on homeBottomSheetMenu[_currentlyOpenMenuIndex]
  Widget _buildMenuItemContainer() {
    if (homeBottomSheetMenu[_currentlyOpenMenuIndex].title == attendanceKey) {
      return const AttendanceContainer();
    } else if (homeBottomSheetMenu[_currentlyOpenMenuIndex].title ==
        timeTableKey) {
      return const TimeTableContainer();
    } else if (homeBottomSheetMenu[_currentlyOpenMenuIndex].title ==
        settingsKey) {
      return const SettingsContainer();
    } else if (homeBottomSheetMenu[_currentlyOpenMenuIndex].title ==
        noticeBoardKey) {
      return const NoticeBoardContainer(showBackButton: false);
    } else if (homeBottomSheetMenu[_currentlyOpenMenuIndex].title ==
        parentProfileKey) {
      return const ParentProfileContainer();
    } else if (homeBottomSheetMenu[_currentlyOpenMenuIndex].title ==
        academicCalendarKey) {
      return BlocProvider(
        create: (context) => AcademicCalendarCubit(
          SystemRepository(),
          StudentRepository(),
          appConfigurationCubit: context.read<AppConfigurationCubit>(),
        ),
        child: const AcademicCalendarScreen(hasBack: false),
      );
    } else if (homeBottomSheetMenu[_currentlyOpenMenuIndex].title == examsKey) {
      return BlocProvider<SemestersCubit>(
        create: (context) => SemestersCubit(),
        child: const ExamContainer(),
      );
    } else if (homeBottomSheetMenu[_currentlyOpenMenuIndex].title ==
        resultKey) {
      return BlocProvider<SemestersCubit>(
        create: (context) => SemestersCubit(),
        child: const ResultsContainer(),
      );
    } else if (homeBottomSheetMenu[_currentlyOpenMenuIndex].title ==
        reportsKey) {
      return ReportsScreen.builder(context);
    } else {
      return const SizedBox();
    }
  }

  bool canPopScreen() {
    if (_isMoreMenuOpen) {
      return false;
    }
    if (_currentSelectedStackItemIndex != 0) {
      return false;
    }
    return true;
  }

  @override
  Widget build(BuildContext context) {
    final currentContext = context.watch<MeContextCubit>().state.context;
    final chatOn = allowsSchoolModule(currentContext, 'chat');
    if (!chatOn && _currentSelectedStackItemIndex == 1) {
      _currentSelectedStackItemIndex = 0;
      _currentSelectedBottomNavIndex = 0;
    }
    return PopScope(
      canPop: canPopScreen(),
      onPopInvokedWithResult: (value, dynamic result) {
        if (_isMoreMenuOpen) {
          _closeBottomMenu();
          return;
        }
        if (_currentSelectedStackItemIndex != 0) {
          changeBottomNavItem(0);
          return;
        }
      },
      child: Scaffold(
        body: context.read<AppConfigurationCubit>().appUnderMaintenance()
            ? const AppUnderMaintenanceContainer()
            : AdaptiveScaffold(
                selectedIndex: _visibleNavIndices
                    .indexOf(_currentSelectedBottomNavIndex)
                    .clamp(0, _visibleNavIndices.length - 1),
                onDestinationSelected: (index) =>
                    changeBottomNavItem(_visibleNavIndices[index]),
                destinations: _buildRailDestinations(),
                body: Stack(
                  children: [
                    IndexedStack(
                      index: _currentSelectedStackItemIndex,
                      children: [
                        const HomeContainer(),
                        ModuleAccessPage(
                          module: 'chat',
                          builder: (_) => const ChatUsersScreen(),
                        ),
                        const AssignmentsContainer(),
                        _currentlyOpenMenuIndex != -1
                            ? _buildMenuItemContainer()
                            : const SizedBox(),
                      ],
                    ),
                    IgnorePointer(
                      ignoring: !_isMoreMenuOpen,
                      child: FadeTransition(
                        opacity: _moreMenuBackgroundContainerColorAnimation,
                        child: _buildMoreMenuBackgroundContainer(),
                      ),
                    ),
                    //More menu bottom sheet
                    Align(
                      alignment: Alignment.bottomCenter,
                      child: SlideTransition(
                        position: _moreMenuBottomsheetAnimation,
                        child: MoreMenuBottomsheetContainer(
                          closeBottomMenu: _closeBottomMenu,
                          onTapMoreMenuItemContainer:
                              _onTapMoreMenuItemContainer,
                        ),
                      ),
                    ),
                  ],
                ),
                bottomNavigationBar: _buildBottomNavigationContainer(),
                overlays: [
                  context.read<AppConfigurationCubit>().forceUpdate()
                      ? FutureBuilder<bool>(
                          future: UiUtils.forceUpdate(
                            context
                                .read<AppConfigurationCubit>()
                                .getAppVersion(),
                          ),
                          builder: (context, snaphsot) {
                            if (snaphsot.hasData) {
                              return (snaphsot.data ?? false)
                                  ? const ForceUpdateDialogContainer()
                                  : const SizedBox();
                            }
                            return const SizedBox();
                          },
                        )
                      : const SizedBox(),
                ],
              ),
      ),
    );
  }

  List<int> get _visibleNavIndices => [
    0,
    if (allowsSchoolModule(
      context.read<MeContextCubit>().state.context,
      'chat',
    ))
      1,
    2,
    3,
  ];

  /// The bottom bar's items as a navigation rail, for tablets and desktops.
  List<AdaptiveDestination> _buildRailDestinations() {
    //phones draw the bottom bar, which follows the chat count itself
    if (Breakpoints.isPhone(context)) {
      return const [];
    }
    final chatState = context.watch<ChatUsersCubit>().state;
    return [
      for (final index in _visibleNavIndices)
        AdaptiveDestination(
          icon: SvgPicture.asset(_bottomNavItems[index].disableImageUrl),
          selectedIcon: SvgPicture.asset(_bottomNavItems[index].activeImageUrl),
          label: UiUtils.getTranslatedLabel(
            context,
            _bottomNavItems[index].title,
          ),
          badgeCount: index == 1 && chatState is ChatUsersFetchSuccess
              ? chatState.totalUnreadUsers
              : null,
        ),
    ];
  }
}
