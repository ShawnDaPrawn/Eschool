import 'package:eschool/app/routes.dart';
import 'package:eschool/cubits/appConfigurationCubit.dart';
import 'package:eschool/ui/widgets/customRoundedButton.dart';
import 'package:eschool/utils/assets.dart';
import 'package:eschool/utils/constants.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_svg/svg.dart';
import 'package:lottie/lottie.dart';
import 'package:url_launcher/url_launcher.dart';

class AuthScreen extends StatefulWidget {
  const AuthScreen({super.key});

  @override
  State<AuthScreen> createState() => _AuthScreenState();
}

class _AuthScreenState extends State<AuthScreen> with TickerProviderStateMixin {
  late final AnimationController _bottomMenuHeightAnimationController =
      AnimationController(
        vsync: this,
        duration: const Duration(milliseconds: 600),
      );

  late final Animation<double> _bottomMenuHeightUpAnimation =
      Tween<double>(begin: 0, end: 1).animate(
        CurvedAnimation(
          parent: _bottomMenuHeightAnimationController,
          curve: const Interval(0, 0.6, curve: Curves.easeInOut),
        ),
      );
  late final Animation<double> _bottomMenuHeightDownAnimation =
      Tween<double>(begin: 0, end: 1).animate(
        CurvedAnimation(
          parent: _bottomMenuHeightAnimationController,
          curve: const Interval(0.6, 1, curve: Curves.easeInOut),
        ),
      );

  Future<void> startAnimation() async {
    //cupertino page transition duration
    await Future.delayed(const Duration(milliseconds: 300));

    _bottomMenuHeightAnimationController.forward();
  }

  @override
  void initState() {
    super.initState();
    startAnimation();
  }

  @override
  void dispose() {
    _bottomMenuHeightAnimationController.dispose();
    super.dispose();
  }

  void _launchSchoolWebsiteWebview() {
    launchUrl(
      Uri.parse(baseUrl),
      mode: LaunchMode.inAppBrowserView,
      webOnlyWindowName: context
          .read<AppConfigurationCubit>()
          .getAppConfiguration()
          .schoolName,
    );
  }

  Widget _buildLottieAnimation() {
    return Align(
      alignment: Alignment.topCenter,
      child: Container(
        width: MediaQuery.sizeOf(context).width,
        margin: EdgeInsets.only(
          top:
              MediaQuery.of(context).padding.top +
              MediaQuery.sizeOf(context).height * 0.05,
        ),
        height: MediaQuery.sizeOf(context).height * 0.4,
        child: Lottie.asset(Assets.onboardingAnimation),
      ),
    );
  }

  Widget _buildSchoolWebsiteButton() {
    return GestureDetector(
      onTap: _launchSchoolWebsiteWebview,
      child: Row(
        mainAxisSize: MainAxisSize.min,
        spacing: 8,
        children: [
          SvgPicture.asset(
            Assets.linkIcon,
            colorFilter: ColorFilter.mode(
              UiUtils.getColorScheme(context).primary,
              BlendMode.srcIn,
            ),
          ),
          Text(
            UiUtils.getTranslatedLabel(context, exploreSchoolKey),
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w600,
              color: UiUtils.getColorScheme(context).primary,
              decorationStyle: TextDecorationStyle.solid,
              decoration: TextDecoration.underline,
              decorationColor: UiUtils.getColorScheme(context).primary,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBottomMenu() {
    return Align(
      alignment: Alignment.bottomCenter,
      child: AnimatedBuilder(
        animation: _bottomMenuHeightAnimationController,
        builder: (context, child) {
          final height =
              MediaQuery.sizeOf(context).height *
                  0.525 *
                  _bottomMenuHeightUpAnimation.value -
              MediaQuery.sizeOf(context).height *
                  0.05 *
                  _bottomMenuHeightDownAnimation.value;
          return Container(
            width: MediaQuery.sizeOf(context).width,
            height: height,
            decoration: BoxDecoration(
              color: Theme.of(context).scaffoldBackgroundColor,
              borderRadius: const BorderRadius.only(
                topLeft: Radius.circular(30),
                topRight: Radius.circular(30),
              ),
            ),
            child: AnimatedSwitcher(
              switchInCurve: Curves.easeInOut,
              duration: const Duration(milliseconds: 400),
              child: _bottomMenuHeightAnimationController.value != 1.0
                  ? const SizedBox()
                  : LayoutBuilder(
                      builder: (context, boxConstraints) {
                        return Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Padding(
                              padding: EdgeInsets.symmetric(
                                horizontal:
                                    MediaQuery.sizeOf(context).width * 0.1,
                              ),
                              child: Text(
                                context
                                    .read<AppConfigurationCubit>()
                                    .getAppConfiguration()
                                    .schoolName,
                                maxLines: 2,
                                textAlign: TextAlign.center,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(
                                  fontSize: 24,
                                  fontWeight: FontWeight.w600,
                                  color: UiUtils.getColorScheme(
                                    context,
                                  ).secondary,
                                ),
                              ),
                            ),
                            SizedBox(height: boxConstraints.maxHeight * 0.0125),
                            Padding(
                              padding: EdgeInsets.symmetric(
                                horizontal:
                                    MediaQuery.sizeOf(context).width * 0.1,
                              ),
                              child: Text(
                                context
                                    .read<AppConfigurationCubit>()
                                    .getAppConfiguration()
                                    .schoolTagline,
                                textAlign: TextAlign.center,
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w300,
                                  color: UiUtils.getColorScheme(
                                    context,
                                  ).onSurface,
                                ),
                              ),
                            ),
                            SizedBox(height: boxConstraints.maxHeight * 0.05),
                            CustomRoundedButton(
                              onTap: () {
                                Navigator.of(
                                  context,
                                ).pushNamed(Routes.studentLogin);
                              },
                              widthPercentage: 0.8,
                              backgroundColor: UiUtils.getColorScheme(
                                context,
                              ).primary,
                              buttonTitle:
                                  '${UiUtils.getTranslatedLabel(context, loginAsKey)} ${UiUtils.getTranslatedLabel(context, studentKey)}',
                              showBorder: false,
                            ),
                            SizedBox(height: boxConstraints.maxHeight * 0.04),
                            CustomRoundedButton(
                              onTap: () {
                                Navigator.of(
                                  context,
                                ).pushNamed(Routes.parentLogin);
                              },
                              widthPercentage: 0.8,
                              backgroundColor: Theme.of(
                                context,
                              ).scaffoldBackgroundColor,
                              buttonTitle:
                                  '${UiUtils.getTranslatedLabel(context, loginAsKey)} ${UiUtils.getTranslatedLabel(context, parentKey)}',
                              titleColor: UiUtils.getColorScheme(
                                context,
                              ).primary,
                              showBorder: true,
                              borderColor: UiUtils.getColorScheme(
                                context,
                              ).primary,
                            ),
                            SizedBox(height: boxConstraints.maxHeight * 0.04),
                            SizedBox(
                              width:
                                  (MediaQuery.sizeOf(context).width * 0.8) - 4,
                              child: Divider(
                                color: UiUtils.getColorScheme(context).primary,
                                thickness: 1,
                                height: 1,
                              ),
                            ),
                            SizedBox(height: boxConstraints.maxHeight * 0.04),
                            _buildSchoolWebsiteButton(),
                            SizedBox(height: boxConstraints.maxHeight * 0.04),
                          ],
                        );
                      },
                    ),
            ),
          );
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).colorScheme.primary,
      body: Stack(children: [_buildLottieAnimation(), _buildBottomMenu()]),
    );
  }
}
