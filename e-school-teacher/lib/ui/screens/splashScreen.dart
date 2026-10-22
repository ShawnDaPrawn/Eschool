import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/cubits/appConfigurationCubit.dart';
import 'package:eschool_teacher/cubits/authCubit.dart';
import 'package:eschool_teacher/cubits/userProfileCubit.dart';
import 'package:eschool_teacher/data/repositories/authRepository.dart';
import 'package:eschool_teacher/data/repositories/policyRepository.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/utils/assets.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_svg/flutter_svg.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();

  static Route route(RouteSettings routeSettings) {
    return AdaptivePageRoute(
      builder: (_) => MultiBlocProvider(
        providers: [
          BlocProvider<UserProfileCubit>(
            create: (_) => UserProfileCubit(AuthRepository()),
          ),
        ],
        child: const SplashScreen(),
      ),
    );
  }
}

class _SplashScreenState extends State<SplashScreen> {
  final Duration _minAnimationDuration = const Duration(seconds: 2);

  final int _navigationCriteriaTotal = 2;
  int _navigationCriteria = 0;
  bool _policiesPending = false;
  String? _policyError;
  bool _leftSplash = false;

  @override
  void initState() {
    super.initState();
    Future.delayed(_minAnimationDuration, () {
      navigateToNextScreen();
    });
    fetchAppConfiguration();
  }

  void fetchAndSetUserProfile() {
    Future.delayed(Duration.zero, () {
      if (context.mounted) {
        context.read<UserProfileCubit>().fetchAndSetUserProfile();
      }
    });
  }

  /// A teacher who already accepted on their first sign-in goes home.
  Future<void> _decideEntry() async {
    setState(() => _policyError = null);
    try {
      final status = await PolicyRepository().status();
      if (!mounted) {
        return;
      }
      if (PolicyRepository.pending(status)) {
        _policiesPending = true;
        navigateToNextScreen();
      } else {
        fetchAndSetUserProfile();
      }
    } on ApiException catch (error) {
      if (!mounted) {
        return;
      }
      setState(() => _policyError = error.errorMessage);
    } catch (error) {
      if (!mounted) {
        return;
      }
      setState(() => _policyError = error.toString());
    }
  }

  void fetchAppConfiguration() {
    Future.delayed(Duration.zero, () {
      if (context.mounted) {
        context.read<AppConfigurationCubit>().fetchAppConfiguration();
      }
    });
  }

  void navigateToNextScreen() {
    if (_leftSplash || !mounted) {
      return;
    }
    _navigationCriteria++;
    if (_navigationCriteria >= _navigationCriteriaTotal) {
      _leftSplash = true;
      if (context.read<AuthCubit>().state is Unauthenticated) {
        Navigator.of(context).pushReplacementNamed(Routes.login);
      } else if (_policiesPending) {
        Navigator.of(context).pushReplacementNamed(Routes.policies);
      } else {
        Navigator.of(context).pushReplacementNamed(Routes.home);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: BlocConsumer<UserProfileCubit, UserProfileState>(
        listener: (context, profileState) {
          if (profileState is UserProfileFetchSuccess) {
            navigateToNextScreen();
          }
        },
        builder: (context, profileState) {
          return BlocConsumer<AppConfigurationCubit, AppConfigurationState>(
            listener: (context, appConfigState) {
              if (appConfigState is AppConfigurationFetchSuccess) {
                if (context.read<AuthCubit>().state is Authenticated) {
                  _decideEntry();
                } else {
                  fetchAndSetUserProfile();
                }
              }
            },
            builder: (context, appConfigState) {
              if (_policyError != null) {
                return Center(
                  child: ErrorContainer(
                    onTapRetry: _decideEntry,
                    errorMessageText: _policyError,
                  ),
                );
              } else if (appConfigState is AppConfigurationFetchFailure) {
                return Center(
                  child: ErrorContainer(
                    onTapRetry: () {
                      fetchAppConfiguration();
                    },
                    errorMessageCode: appConfigState.errorMessage,
                  ),
                );
              } else if (profileState is UserProfileFetchFailure) {
                return Center(
                  child: ErrorContainer(
                    onTapRetry: () {
                      fetchAndSetUserProfile();
                    },
                    errorMessageCode: profileState.errorMessage,
                  ),
                );
              }
              return Center(
                child: Animate(
                  effects: customItemZoomAppearanceEffects(
                    delay: const Duration(milliseconds: 10),
                    duration: const Duration(seconds: 1),
                  ),
                  child: SvgPicture.asset(Assets.appLogo),
                ),
              );
            },
          );
        },
      ),
    );
  }
}
