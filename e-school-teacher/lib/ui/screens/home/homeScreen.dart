import 'package:eschool_teacher/cubits/appConfigurationCubit.dart';
import 'package:eschool_teacher/cubits/chat/chatUsersCubit.dart';
import 'package:eschool_teacher/cubits/timeTableCubit.dart';
import 'package:eschool_teacher/data/repositories/settingsRepository.dart';
import 'package:eschool_teacher/data/repositories/teacherRepository.dart';
import 'package:eschool_teacher/ui/screens/home/tabs/homeContainer/homeContainer.dart';
import 'package:eschool_teacher/ui/screens/home/tabs/profileContainer.dart';
import 'package:eschool_teacher/ui/screens/home/tabs/settingsContainer.dart';
import 'package:eschool_teacher/ui/screens/home/tabs/timeTableContainer.dart';
import 'package:eschool_teacher/ui/screens/home/widgets/appUnderMaintenanceContainer.dart';
import 'package:eschool_teacher/ui/screens/home/widgets/bottomNavigationItemContainer.dart';
import 'package:eschool_teacher/ui/screens/home/widgets/forceUpdateDialogContainer.dart';
import 'package:eschool_teacher/utils/assets.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/notificationUtils/chatNotificationsUtils.dart';
import 'package:eschool_teacher/utils/notificationUtils/generalNotificationUtility.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

//value notifier to show notification icon active/deavtive
ValueNotifier<int> notificationCountValueNotifier = ValueNotifier(0);

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();

  static Route<dynamic> route(RouteSettings routeSettings) {
    return CupertinoPageRoute(
      builder: (_) => BlocProvider(
        create: (context) => TimeTableCubit(TeacherRepository()),
        child: const HomeScreen(),
      ),
    );
  }
}

class _HomeScreenState extends State<HomeScreen>
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

  late int _currentSelectedBottomNavIndex = 0;

  final List<BottomNavItem> _bottomNavItems = [
    BottomNavItem(
      activeImageUrl: Assets.homeTabActiveIcon,
      disableImageUrl: Assets.homeTabIcon,
      title: homeKey,
    ),
    BottomNavItem(
      activeImageUrl: Assets.scheduleTabActiveIcon,
      disableImageUrl: Assets.scheduleTabIcon,
      title: scheduleKey,
    ),
    BottomNavItem(
      activeImageUrl: Assets.profileTabActiveIcon,
      disableImageUrl: Assets.profileTabIcon,
      title: profileKey,
    ),
    BottomNavItem(
      activeImageUrl: Assets.settingsTabActiveIcon,
      disableImageUrl: Assets.settingsTabIcon,
      title: settingKey,
    ),
  ];

  @override
  void initState() {
    initAnimations();
    _animationController.forward();
    _animationController.forward();
    WidgetsBinding.instance.addObserver(this);
    Future.delayed(Duration.zero, () {
      if (context.mounted) {
        //setup notification callback here
        NotificationUtility.setUpNotificationService(context);
        NotificationUtility.subscribeOrUnsubscribeToNotificationTopics(
          isUnsubscribe: false,
        );
        //fetch chat users for chat count
        context.read<StudentChatUsersCubit>().fetchChatUsers();
        context.read<ParentChatUserCubit>().fetchChatUsers();
      }
    });
    super.initState();
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
  void dispose() {
    _animationController.dispose();
    WidgetsBinding.instance.removeObserver(this);
    for (final animationController in _bottomNavItemTitlesAnimationController) {
      animationController.dispose();
    }
    super.dispose();
  }

  Future<void> _changeBottomNavItem(int index) async {
    _bottomNavItemTitlesAnimationController[_currentSelectedBottomNavIndex]
        .forward();
    //change current selected bottom index
    setState(() {
      _currentSelectedBottomNavIndex = index;
    });
    _bottomNavItemTitlesAnimationController[_currentSelectedBottomNavIndex]
        .reverse();
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
          margin: EdgeInsets.only(bottom: UiUtils.bottomNavigationBottomMargin),
          decoration: BoxDecoration(
            boxShadow: [
              BoxShadow(
                color: UiUtils.getColorScheme(
                  context,
                ).secondary.withValues(alpha: 0.15),
                offset: const Offset(2.5, 2.5),
                blurRadius: 20,
              ),
            ],
            color: Theme.of(context).scaffoldBackgroundColor,
            borderRadius: BorderRadius.circular(10),
          ),
          width: MediaQuery.sizeOf(context).width * 0.8,
          height:
              MediaQuery.sizeOf(context).height *
              UiUtils.bottomNavigationHeightPercentage,
          child: LayoutBuilder(
            builder: (context, boxConstraints) {
              return Row(
                mainAxisAlignment: MainAxisAlignment.spaceAround,
                children: _bottomNavItems.map((bottomNavItem) {
                  final int index = _bottomNavItems.indexWhere(
                    (e) => e.title == bottomNavItem.title,
                  );
                  return BottomNavItemContainer(
                    onTap: _changeBottomNavItem,
                    boxConstraints: boxConstraints,
                    currentIndex: _currentSelectedBottomNavIndex,
                    bottomNavItem: _bottomNavItems[index],
                    animationController:
                        _bottomNavItemTitlesAnimationController[index],
                    index: index,
                  );
                }).toList(),
              );
            },
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: _currentSelectedBottomNavIndex == 0,
      onPopInvokedWithResult: (didPop, dynamic result) {
        if (!didPop) {
          _changeBottomNavItem(0);
        }
      },
      child: Scaffold(
        body: context.read<AppConfigurationCubit>().appUnderMaintenance()
            ? const AppUnderMaintenanceContainer()
            : Stack(
                children: [
                  IndexedStack(
                    index: _currentSelectedBottomNavIndex,
                    children: const [
                      HomeContainer(),
                      TimeTableContainer(),
                      ProfileContainer(),
                      SettingsContainer(),
                    ],
                  ),
                  Align(
                    alignment: Alignment.bottomCenter,
                    child: _buildBottomNavigationContainer(),
                  ),
                  if (context.read<AppConfigurationCubit>().forceUpdate())
                    FutureBuilder<bool>(
                      future: UiUtils.forceUpdate(
                        context.read<AppConfigurationCubit>().getAppVersion(),
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
                  else
                    const SizedBox(),
                ],
              ),
      ),
    );
  }
}
