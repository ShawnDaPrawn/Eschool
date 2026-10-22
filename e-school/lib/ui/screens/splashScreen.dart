import 'package:eschool/app/routes.dart';
import 'package:eschool/cubits/appConfigurationCubit.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/cubits/userProfileCubit.dart';
import 'package:eschool/data/repositories/authRepository.dart';
import 'package:eschool/data/repositories/policyRepository.dart';
import 'package:eschool/ui/widgets/errorContainer.dart';
import 'package:eschool/utils/animationConfiguration.dart';
import 'package:eschool/utils/assets.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_svg/svg.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();

  static Route route(RouteSettings routeSettings) {
    return CupertinoPageRoute(
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
  bool _parentPoliciesPending = false;
  String? _policyError;
  bool _leftSplash = false;

  @override
  void initState() {
    super.initState();
    Future.delayed(_minAnimationDuration, () {
      navigateToNextScreen();
    });

    Future.delayed(Duration.zero, () {
      context.read<AppConfigurationCubit>().fetchAppConfiguration();
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
        Navigator.of(context).pushReplacementNamed(Routes.auth);
      } else {
        if ((context.read<AuthCubit>().state as Authenticated).isStudent) {
          if (context.read<AuthCubit>().getStudentDetails().isFeePaymentDue &&
              context
                  .read<AppConfigurationCubit>()
                  .isCompulsoryFeePaymentMode()) {
            Navigator.of(
              context,
            ).pushReplacementNamed(Routes.studentFeePaymentDueScreen);
          } else {
            Navigator.of(context).pushReplacementNamed(Routes.home);
          }
        } else if (_parentPoliciesPending) {
          Navigator.of(context).pushReplacementNamed(Routes.parentPolicies);
        } else {
          Navigator.of(context).pushReplacementNamed(Routes.parentHome);
        }
      }
    }
  }

  void fetchAndSetUserProfile() {
    Future.delayed(Duration.zero, () {
      context.read<UserProfileCubit>().fetchAndSetUserProfile(autoLogout: true);
    });
  }

  /// Returning parents go home. The terms screen is only for a first sign-in
  /// that has not been accepted yet.
  Future<void> _decideParentEntry() async {
    setState(() => _policyError = null);
    try {
      final status = await PolicyRepository().status();
      if (!mounted) {
        return;
      }
      if (PolicyRepository.pending(status)) {
        _parentPoliciesPending = true;
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: BlocConsumer<UserProfileCubit, UserProfileState>(
        listener: (context, profileState) {
          if (profileState is UserProfileFetchSuccess) {
            //updating profile in auth cubit as it's accessed throughout the application
            if (context.read<AuthCubit>().isParent()) {
              context.read<AuthCubit>().updateParentProfile(
                parent: AuthRepository().getParentDetails(),
              );
            } else {
              context.read<AuthCubit>().updateStudentProfile(
                student: AuthRepository().getStudentDetails(),
              );
            }
            navigateToNextScreen();
          }
        },
        builder: (context, profileState) {
          return BlocConsumer<AppConfigurationCubit, AppConfigurationState>(
            listener: (context, appConfigState) {
              if (appConfigState is AppConfigurationFetchSuccess) {
                if (context.read<AuthCubit>().isParent()) {
                  _decideParentEntry();
                } else {
                  fetchAndSetUserProfile();
                }
              }
            },
            builder: (context, appConfigState) {
              if (_policyError != null) {
                return Center(
                  child: ErrorContainer(
                    onTapRetry: _decideParentEntry,
                    errorMessageText: _policyError,
                  ),
                );
              } else if (appConfigState is AppConfigurationFetchFailure) {
                return Center(
                  child: ErrorContainer(
                    onTapRetry: () {
                      context
                          .read<AppConfigurationCubit>()
                          .fetchAppConfiguration();
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
                  child: SvgPicture.asset(
                    Theme.of(context).brightness == Brightness.dark
                        ? Assets.appLogoReversed
                        : Assets.appLogo,
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
