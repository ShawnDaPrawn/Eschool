import 'package:dotted_line/dotted_line.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/cubits/appConfigurationCubit.dart';
import 'package:eschool_teacher/cubits/authCubit.dart';
import 'package:eschool_teacher/cubits/forgotPasswordRequestCubit.dart';
import 'package:eschool_teacher/cubits/signInCubit.dart';
import 'package:eschool_teacher/data/repositories/authRepository.dart';
import 'package:eschool_teacher/data/repositories/settingsRepository.dart';
import 'package:eschool_teacher/ui/screens/login/widgets/forgotPasswordRequestBottomsheet.dart';
import 'package:eschool_teacher/ui/screens/login/widgets/termsAndConditionAndPrivacyPolicyContainer.dart';
import 'package:eschool_teacher/ui/widgets/customCircularProgressIndicator.dart';
import 'package:eschool_teacher/ui/widgets/customRoundedButton.dart';
import 'package:eschool_teacher/ui/widgets/passwordHideShowButton.dart';
import 'package:eschool_teacher/utils/assets.dart';
import 'package:eschool_teacher/utils/constants.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:url_launcher/url_launcher.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();

  static Route<dynamic> route(RouteSettings routeSettings) {
    return CupertinoPageRoute(
      builder: (_) => BlocProvider<SignInCubit>(
        child: const LoginScreen(),
        create: (_) => SignInCubit(AuthRepository()),
      ),
    );
  }
}

class _LoginScreenState extends State<LoginScreen>
    with TickerProviderStateMixin {
  late final AnimationController _animationController = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 1000),
  );

  late final Animation<double> _patterntAnimation =
      Tween<double>(begin: 0, end: 1).animate(
        CurvedAnimation(
          parent: _animationController,
          curve: const Interval(0, 0.5, curve: Curves.easeInOut),
        ),
      );

  late final Animation<double> _formAnimation = Tween<double>(begin: 0, end: 1)
      .animate(
        CurvedAnimation(
          parent: _animationController,
          curve: const Interval(0.5, 1, curve: Curves.easeInOut),
        ),
      );

  bool _hidePassword = true;

  final TextEditingController _emailTextEditingController =
      TextEditingController(
        text: showDefaultCredentials ? defaultTeacherEmail : null,
      ); //DEFAULT EMAIL

  final TextEditingController _passwordTextEditingController =
      TextEditingController(
        text: showDefaultCredentials ? defaultTeacherPassword : null,
      ); //DEFAULT PASSWORD

  @override
  void initState() {
    super.initState();
    _animationController.forward();
  }

  @override
  void dispose() {
    _animationController.dispose();
    _emailTextEditingController.dispose();
    _passwordTextEditingController.dispose();
    super.dispose();
  }

  void _signInTeacher() {
    if (_emailTextEditingController.text.trim().isEmpty) {
      UiUtils.showBottomToastOverlay(
        context: context,
        errorMessage: UiUtils.getTranslatedLabel(context, pleaseEnterEmailKey),
        backgroundColor: Theme.of(context).colorScheme.error,
      );
      return;
    }

    if (_passwordTextEditingController.text.trim().isEmpty) {
      UiUtils.showBottomToastOverlay(
        context: context,
        errorMessage: UiUtils.getTranslatedLabel(
          context,
          pleaseEnterPasswordKey,
        ),
        backgroundColor: Theme.of(context).colorScheme.error,
      );
      return;
    }

    context.read<SignInCubit>().signInUser(
      email: _emailTextEditingController.text.trim(),
      password: _passwordTextEditingController.text.trim(),
    );
  }

  Widget _buildUpperPattern() {
    return Align(
      alignment: Alignment.topRight,
      child: FadeTransition(
        opacity: _patterntAnimation,
        child: SlideTransition(
          position: _patterntAnimation.drive(
            Tween<Offset>(begin: const Offset(0, -1), end: Offset.zero),
          ),
          child: SvgPicture.asset(Assets.upperPattern, fit: BoxFit.fitWidth),
        ),
      ),
    );
  }

  Widget _buildLowerPattern() {
    return Align(
      alignment: Alignment.bottomLeft,
      child: FadeTransition(
        opacity: _patterntAnimation,
        child: SlideTransition(
          position: _patterntAnimation.drive(
            Tween<Offset>(begin: const Offset(0, 1), end: Offset.zero),
          ),
          child: SvgPicture.asset(Assets.lowerPattern, fit: BoxFit.fitWidth),
        ),
      ),
    );
  }

  Widget _buildForgotPassword() {
    return Align(
      alignment: AlignmentDirectional.centerEnd,
      child: Padding(
        padding: const EdgeInsets.only(top: 8),
        child: GestureDetector(
          onTap: () {
            //
            if (context.read<SignInCubit>().state is SignInInProgress) {
              return;
            }
            //
            if (UiUtils.isDemoVersionEnable(context: context)) {
              UiUtils.showFeatureDisableInDemoVersion(context);
              return;
            }
            UiUtils.showBottomSheet(
              child: BlocProvider(
                create: (_) => ForgotPasswordRequestCubit(AuthRepository()),
                child: const ForgotPasswordRequestBottomsheet(),
              ),
              context: context,
            ).then((value) {
              if (value != null && !value['error']) {
                if (context.mounted) {
                  UiUtils.showBottomToastOverlay(
                    context: context,
                    errorMessage:
                        "${UiUtils.getTranslatedLabel(context, passwordUpdateLinkSentKey)} ${value['email']}",
                    backgroundColor: Theme.of(context).colorScheme.onPrimary,
                  );
                }
              }
            });
          },
          child: Text(
            '${UiUtils.getTranslatedLabel(context, forgotPasswordKey)}?',
            style: TextStyle(color: Theme.of(context).colorScheme.primary),
          ),
        ),
      ),
    );
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

  Widget _buildOrRow() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 4),
      child: Row(
        spacing: 8,
        children: [
          Expanded(
            child: DottedLine(
              dashColor: UiUtils.getColorScheme(
                context,
              ).secondary.withAlpha(80),
              dashGapLength: 2,
              dashLength: 3,
            ),
          ),
          Text(
            UiUtils.getTranslatedLabel(context, orKey),
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w400,
              color: UiUtils.getColorScheme(context).secondary,
            ),
          ),
          Expanded(
            child: DottedLine(
              dashColor: UiUtils.getColorScheme(
                context,
              ).secondary.withAlpha(80),
              dashGapLength: 2,
              dashLength: 3,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLoginForm() {
    return Align(
      alignment: AlignmentDirectional.topStart,
      child: FadeTransition(
        opacity: _formAnimation,
        child: Padding(
          padding: EdgeInsets.only(
            bottom: MediaQuery.of(context).viewInsets.bottom,
          ), //to make UI scrollable when keyboard is opened
          child: SingleChildScrollView(
            padding: EdgeInsets.only(
              left: MediaQuery.sizeOf(context).width * 0.075,
              right: MediaQuery.sizeOf(context).width * 0.075,
              top: MediaQuery.sizeOf(context).height * 0.25,
            ),
            child: Form(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    UiUtils.getTranslatedLabel(context, letsSignInKey),
                    style: TextStyle(
                      fontSize: 34,
                      fontWeight: FontWeight.bold,
                      color: UiUtils.getColorScheme(context).secondary,
                    ),
                  ),
                  const SizedBox(height: 10),
                  Text(
                    '${UiUtils.getTranslatedLabel(context, welcomeBackKey)}, \n${UiUtils.getTranslatedLabel(context, youHaveBeenMissedKey)}',
                    style: TextStyle(
                      fontSize: 24,
                      height: 1.5,
                      color: UiUtils.getColorScheme(context).secondary,
                    ),
                  ),
                  const SizedBox(height: 30),
                  Container(
                    padding: const EdgeInsets.only(left: 20),
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(
                        color: UiUtils.getColorScheme(context).secondary,
                      ),
                    ),
                    child: TextFormField(
                      controller: _emailTextEditingController,
                      textAlignVertical: TextAlignVertical.center,
                      keyboardType: TextInputType.emailAddress,
                      decoration: InputDecoration(
                        suffixIcon: Padding(
                          padding: const EdgeInsets.all(12),
                          child: SvgPicture.asset(
                            Assets.mailIcon,
                            colorFilter: ColorFilter.mode(
                              UiUtils.getColorScheme(context).secondary,
                              BlendMode.srcIn,
                            ),
                            // color: UiUtils.getColorScheme(context).secondary,
                          ),
                        ),
                        hintStyle: TextStyle(
                          color: UiUtils.getColorScheme(context).secondary,
                        ),
                        hintText: UiUtils.getTranslatedLabel(context, emailKey),
                        border: InputBorder.none,
                      ),
                    ),
                  ),
                  const SizedBox(height: 30),
                  Container(
                    padding: const EdgeInsets.only(left: 20),
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(
                        color: UiUtils.getColorScheme(context).secondary,
                      ),
                    ),
                    child: TextFormField(
                      controller: _passwordTextEditingController,
                      obscureText: _hidePassword,
                      textAlignVertical: TextAlignVertical.center,
                      decoration: InputDecoration(
                        suffixIcon: PasswordHideShowButton(
                          hidePassword: _hidePassword,
                          onTap: () {
                            setState(() {
                              _hidePassword = !_hidePassword;
                            });
                          },
                        ),
                        hintStyle: TextStyle(
                          color: UiUtils.getColorScheme(context).secondary,
                        ),
                        hintText: UiUtils.getTranslatedLabel(
                          context,
                          passwordKey,
                        ),
                        border: InputBorder.none,
                      ),
                    ),
                  ),
                  _buildForgotPassword(),
                  const SizedBox(height: 20),
                  Center(
                    child: BlocConsumer<SignInCubit, SignInState>(
                      listener: (context, state) {
                        if (state is SignInSuccess) {
                          context.read<AuthCubit>().authenticateUser(
                            jwtToken: state.jwtToken,
                            teacher: state.teacher,
                          );
                          //somehow user logs out, the login will set count to 0
                          SettingsRepository().setNotificationCount(0);
                          Navigator.of(
                            context,
                          ).pushReplacementNamed(Routes.home);
                        } else if (state is SignInFailure) {
                          UiUtils.showBottomToastOverlay(
                            context: context,
                            errorMessage: UiUtils.getErrorMessageFromErrorCode(
                              context,
                              state.exception,
                            ),
                            backgroundColor: Theme.of(
                              context,
                            ).colorScheme.error,
                          );
                        }
                      },
                      builder: (context, state) {
                        return CustomRoundedButton(
                          onTap: () {
                            if (state is SignInInProgress) {
                              return;
                            }

                            FocusScope.of(context).unfocus();
                            _signInTeacher();
                          },
                          widthPercentage: 0.85,
                          backgroundColor: UiUtils.getColorScheme(
                            context,
                          ).primary,
                          buttonTitle: UiUtils.getTranslatedLabel(
                            context,
                            signInKey,
                          ),
                          titleColor: Theme.of(context).scaffoldBackgroundColor,
                          showBorder: false,
                          child: state is SignInInProgress
                              ? const CustomCircularProgressIndicator(
                                  strokeWidth: 2,
                                  widthAndHeight: 20,
                                )
                              : null,
                        );
                      },
                    ),
                  ),
                  const SizedBox(height: 24),
                  _buildOrRow(),
                  const SizedBox(height: 24),
                  Center(child: _buildSchoolWebsiteButton()),
                  const SizedBox(height: 42),
                  const TermsAndConditionAndPrivacyPolicyContainer(),
                  SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      resizeToAvoidBottomInset:
          false, //to avoid the lower pattern from hiding login form when keyboard is open
      body: Stack(
        children: [
          _buildUpperPattern(),
          _buildLowerPattern(),
          _buildLoginForm(),
        ],
      ),
    );
  }
}
