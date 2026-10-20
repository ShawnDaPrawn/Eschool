import 'package:eschool/app/routes.dart';
import 'package:eschool/cubits/appConfigurationCubit.dart';
import 'package:eschool/cubits/askParentsToPayFeesCubit.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/cubits/userProfileCubit.dart';
import 'package:eschool/data/repositories/authRepository.dart';
import 'package:eschool/ui/styles/colors.dart';
import 'package:eschool/ui/widgets/customCircularProgressIndicator.dart';
import 'package:eschool/ui/widgets/customRoundedButton.dart';
import 'package:eschool/ui/widgets/logoutButton.dart';
import 'package:eschool/utils/assets.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_svg/svg.dart';

class StudentFeePaymentDueScreen extends StatefulWidget {
  const StudentFeePaymentDueScreen({super.key});

  @override
  State<StudentFeePaymentDueScreen> createState() =>
      _StudentFeePaymentDueScreenState();

  static Route route(RouteSettings routeSettings) {
    return CupertinoPageRoute(
      builder: (_) => MultiBlocProvider(
        providers: [
          BlocProvider<UserProfileCubit>(
            create: (_) => UserProfileCubit(AuthRepository()),
          ),
          BlocProvider<AskParentsToPayFeesCubit>(
            create: (_) => AskParentsToPayFeesCubit(),
          ),
        ],
        child: const StudentFeePaymentDueScreen(),
      ),
    );
  }
}

class _StudentFeePaymentDueScreenState
    extends State<StudentFeePaymentDueScreen> {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: BlocListener<UserProfileCubit, UserProfileState>(
        listener: (context, state) {
          if (state is UserProfileFetchSuccess) {
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
            if (!context
                .read<AuthCubit>()
                .getStudentDetails()
                .isFeePaymentDue) {
              Navigator.of(context).pushReplacementNamed(Routes.home);
            }
          }
        },
        child: Padding(
          padding: EdgeInsetsDirectional.only(
            start: 15,
            end: 15,
            top: MediaQuery.of(context).viewPadding.top,
            bottom: 15,
          ),
          child: Stack(
            children: [
              Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Flexible(child: SvgPicture.asset(Assets.feePaymentDueImage)),
                  const SizedBox(height: 20),
                  Text(
                    UiUtils.getTranslatedLabel(context, feePaymentDueKey),
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.secondary,
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 10),
                  Text(
                    UiUtils.getTranslatedLabel(
                      context,
                      youCanContinueLearningAfterPayingTheFeesKey,
                    ),
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.secondary,
                      fontSize: 16,
                    ),
                  ),
                  const SizedBox(height: 15),
                  BlocConsumer<
                    AskParentsToPayFeesCubit,
                    AskParentsToPayFeesState
                  >(
                    listener: (context, state) {
                      if (state is AskParentsToPayFeesFailed) {
                        UiUtils.showCustomSnackBar(
                          context: context,
                          errorMessage: UiUtils.getErrorMessageFromErrorCode(
                            context,
                            state.exception,
                          ),
                          backgroundColor: Theme.of(context).colorScheme.error,
                        );
                      }
                    },
                    builder: (context, state) {
                      if (state is AskParentsToPayFeesSuccessfully) {
                        return Text(
                          UiUtils.getTranslatedLabel(
                            context,
                            weHaveAskedYourParentOrGuardianToPayTheFeesKey,
                          ),
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            color: Theme.of(context).colorScheme.primary,
                            fontSize: 14,
                          ),
                        );
                      } else {
                        return CustomRoundedButton(
                          widthPercentage: 0.5,
                          backgroundColor: Theme.of(
                            context,
                          ).colorScheme.primary,
                          onTap: () {
                            if (state is AskParentsToPayFeesInProgress) {
                              return;
                            } else {
                              context
                                  .read<AskParentsToPayFeesCubit>()
                                  .askParentOrGuardianToPayFeesKey();
                            }
                          },
                          titleColor: Theme.of(context).scaffoldBackgroundColor,
                          textAlign: TextAlign.center,
                          buttonTitle: UiUtils.getTranslatedLabel(
                            context,
                            askParentOrGuardianToPayFeesKey,
                          ),
                          showBorder: false,
                          child: state is AskParentsToPayFeesInProgress?
                              ? CustomCircularProgressIndicator(
                                  indicatorColor: Theme.of(
                                    context,
                                  ).scaffoldBackgroundColor,
                                  strokeWidth: 2,
                                  widthAndHeight: 18,
                                )
                              : null,
                        );
                      }
                    },
                  ),
                  //if already asked to pay
                  if (context
                      .read<AppConfigurationCubit>()
                      .canStudentPayTheirFees()) ...[
                    const SizedBox(height: 15),
                    CustomRoundedButton(
                      widthPercentage: 0.5,
                      backgroundColor: Colors.transparent,
                      onTap: () {
                        Navigator.of(context).pushNamed(
                          Routes.feesDetails,
                          arguments: {
                            'studentDetails': context
                                .read<AuthCubit>()
                                .getStudentDetails(),
                            'sessionYearId': context
                                .read<AppConfigurationCubit>()
                                .getAppConfiguration()
                                .sessionYear
                                .id,
                            'isStudentPaying': true,
                          },
                        );
                      },
                      titleColor: Theme.of(context).colorScheme.primary,
                      buttonTitle: UiUtils.getTranslatedLabel(
                        context,
                        payFeesYourselfKey,
                      ),
                      borderColor: Theme.of(context).colorScheme.primary,
                      textAlign: TextAlign.center,
                      showBorder: true,
                    ),
                    BlocBuilder<UserProfileCubit, UserProfileState>(
                      builder: (context, state) {
                        if (state is UserProfileFetchInProgress) {
                          return const Center(
                            child: Padding(
                              padding: EdgeInsets.all(12),
                              child: SizedBox(
                                height: 24,
                                width: 24,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                ),
                              ),
                            ),
                          );
                        } else {
                          return Center(
                            child: GestureDetector(
                              onTap: () {
                                context
                                    .read<UserProfileCubit>()
                                    .fetchAndSetUserProfile();
                              },
                              child: Padding(
                                padding: const EdgeInsets.all(12),
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Icon(
                                      Icons.refresh,
                                      color: Theme.of(
                                        context,
                                      ).colorScheme.primary,
                                      size: 24,
                                    ),
                                    const SizedBox(width: 5),
                                    Text(
                                      UiUtils.getTranslatedLabel(
                                        context,
                                        reCheckPaymentStatusKey,
                                      ),
                                      style: TextStyle(
                                        color: Theme.of(
                                          context,
                                        ).colorScheme.primary,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          );
                        }
                      },
                    ),
                  ],
                ],
              ),
              Align(
                alignment: AlignmentDirectional.topEnd,
                child: Container(
                  decoration: BoxDecoration(
                    color: redColor.withValues(alpha: 0.1),
                    shape: BoxShape.circle,
                  ),
                  child: IconButton(
                    tooltip: UiUtils.getTranslatedLabel(context, logoutKey),
                    icon: const Icon(Icons.logout, color: redColor, size: 30),
                    onPressed: () {
                      LogoutButton.showLogOutDialog(
                        context,
                        isBeforeHomePage: true,
                      );
                    },
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
