import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/cubits/appConfigurationCubit.dart';
import 'package:eschool_teacher/cubits/appLocalizationCubit.dart';
import 'package:eschool_teacher/cubits/changePasswordCubit.dart';
import 'package:eschool_teacher/data/repositories/authRepository.dart';
import 'package:eschool_teacher/ui/screens/home/widgets/changeLanguageBottomsheetContainer.dart';
import 'package:eschool_teacher/ui/screens/home/widgets/changePasswordBottomsheet.dart';
import 'package:eschool_teacher/ui/screens/home/widgets/logoutButton.dart';
import 'package:eschool_teacher/ui/widgets/customAppbar.dart';
import 'package:eschool_teacher/ui/widgets/notificationIconWidget.dart';
import 'package:eschool_teacher/utils/appLanguages.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:url_launcher/url_launcher.dart';

class SettingsContainer extends StatelessWidget {
  const SettingsContainer({super.key});

  Future<void> _shareApp(BuildContext context) async {
    final appUrl = context.read<AppConfigurationCubit>().getAppLink();
    if (await canLaunchUrl(Uri.parse(appUrl))) {
      launchUrl(Uri.parse(appUrl), mode: LaunchMode.externalApplication);
    } else {
      if (context.mounted) {
        UiUtils.showBottomToastOverlay(
          context: context,
          errorMessage: UiUtils.getTranslatedLabel(
            context,
            unableToOpenLinkKey,
          ),
          backgroundColor: Theme.of(context).colorScheme.error,
        );
      }
    }
  }

  Widget _buildAppbar(BuildContext context) {
    return Align(
      alignment: Alignment.topCenter,
      child: CustomAppBar(
        showBackButton: false,
        title: UiUtils.getTranslatedLabel(context, settingKey),
      ),
    );
  }

  Widget _buildSettingDetailsTile({
    required String title,
    required IconData icon,
    required Function onTap,
    required BuildContext context,
    Widget? customIcon,
  }) {
    return InkWell(
      onTap: () {
        onTap();
      },
      child: Padding(
        padding: EdgeInsetsDirectional.only(
          bottom: 10,
          top: 10,
          start: MediaQuery.sizeOf(context).width * 0.075,
          end: MediaQuery.sizeOf(context).width * 0.075,
        ),
        child: DecoratedBox(
          decoration: BoxDecoration(
            border: Border.all(color: Colors.transparent),
          ),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: Theme.of(context).colorScheme.primary,
                  shape: BoxShape.circle,
                ),
                alignment: Alignment.center,
                child: customIcon ?? Icon(icon, color: Colors.white),
              ),
              const SizedBox(width: 15),
              Expanded(
                child: Text(
                  title,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: Theme.of(context).colorScheme.onSurface,
                    fontSize: 15,
                  ),
                ),
              ),
              Icon(
                Icons.chevron_right,
                color: Theme.of(
                  context,
                ).colorScheme.onSurface.withValues(alpha: 0.8),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSettingsContainer(BuildContext context) {
    return Column(
      children: [
        BlocBuilder<AppLocalizationCubit, AppLocalizationState>(
          builder: (context, state) {
            final String languageName = appLanguages
                .where(
                  (element) =>
                      UiUtils.getLocaleFromLanguageCode(element.languageCode) ==
                      state.language,
                )
                .toList()
                .first
                .languageName;
            return _buildSettingDetailsTile(
              icon: Icons.translate,
              title: languageName,
              onTap: () {
                UiUtils.showBottomSheet(
                  child: const ChangeLanguageBottomsheetContainer(),
                  context: context,
                );
              },
              context: context,
            );
          },
        ),
        _buildSettingDetailsTile(
          icon: Icons.notifications, //this is for show only
          customIcon: const NotificationIconWidget(
            size: Size(24, 24),
          ), //this widget will take icon's place
          title: UiUtils.getTranslatedLabel(context, notificationsKey),
          onTap: () {
            Navigator.of(context).pushNamed(Routes.notifications);
          },
          context: context,
        ),
        _buildSettingDetailsTile(
          icon: Icons.password,
          title: UiUtils.getTranslatedLabel(context, changePasswordKey),
          onTap: () {
            if (UiUtils.isDemoVersionEnable(context: context)) {
              UiUtils.showFeatureDisableInDemoVersion(context);
              return;
            }
            UiUtils.showBottomSheet(
              child: BlocProvider<ChangePasswordCubit>(
                create: (_) => ChangePasswordCubit(AuthRepository()),
                child: const ChangePasswordBottomsheet(),
              ),
              context: context,
            ).then((value) {
              if (value != null && !value['error']) {
                if (context.mounted) {
                  UiUtils.showBottomToastOverlay(
                    context: context,
                    errorMessage: UiUtils.getTranslatedLabel(
                      context,
                      passwordChangedSuccessfullyKey,
                    ),
                    backgroundColor: Theme.of(context).colorScheme.onPrimary,
                  );
                }
              }
            });
          },
          context: context,
        ),
        _buildSettingDetailsTile(
          icon: Icons.privacy_tip,
          title: UiUtils.getTranslatedLabel(context, privacyPolicyKey),
          onTap: () {
            Navigator.of(context).pushNamed(Routes.privacyPolicy);
          },
          context: context,
        ),
        _buildSettingDetailsTile(
          icon: Icons.description,
          title: UiUtils.getTranslatedLabel(context, termsAndConditionKey),
          onTap: () {
            Navigator.of(context).pushNamed(Routes.termsAndCondition);
          },
          context: context,
        ),
        _buildSettingDetailsTile(
          icon: Icons.info,
          title: UiUtils.getTranslatedLabel(context, aboutUsKey),
          onTap: () {
            Navigator.of(context).pushNamed(Routes.aboutUs);
          },
          context: context,
        ),
        _buildSettingDetailsTile(
          icon: Icons.contact_support,
          title: UiUtils.getTranslatedLabel(context, contactUsKey),
          onTap: () {
            Navigator.of(context).pushNamed(Routes.contactUs);
          },
          context: context,
        ),
        _buildSettingDetailsTile(
          icon: Icons.star,
          title: UiUtils.getTranslatedLabel(context, rateUsKey),
          onTap: () {
            _shareApp(context);
          },
          context: context,
        ),
        _buildSettingDetailsTile(
          icon: Icons.share,
          title: UiUtils.getTranslatedLabel(context, shareKey),
          onTap: () {
            _shareApp(context);
          },
          context: context,
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        Align(
          alignment: Alignment.topCenter,
          child: SingleChildScrollView(
            padding: EdgeInsetsDirectional.only(
              bottom: UiUtils.getScrollViewBottomPadding(context),
              top:
                  UiUtils.getScrollViewTopPadding(
                    context: context,
                    appBarHeightPercentage:
                        UiUtils.appBarSmallerHeightPercentage,
                  ) -
                  10, //10 is the top padding of first item
            ),
            child: Column(
              children: [
                _buildSettingsContainer(context),
                const SizedBox(height: 25),
                const LogoutButton(),
                const SizedBox(height: 5),
                Text(
                  '${UiUtils.getTranslatedLabel(context, appVersionKey)}  ${context.read<AppConfigurationCubit>().getAppVersion()}',
                  style: TextStyle(
                    color: Theme.of(context).colorScheme.onSurface,
                    fontWeight: FontWeight.w500,
                    fontSize: 11,
                  ),
                  textAlign: TextAlign.start,
                ),
                //extra height to avoid dashboard's bottom navigation bar
                SizedBox(
                  height:
                      MediaQuery.sizeOf(context).height *
                      UiUtils.bottomNavigationHeightPercentage,
                ),
              ],
            ),
          ),
        ),
        _buildAppbar(context),
      ],
    );
  }
}
