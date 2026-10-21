import 'package:eschool/app/routes.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/utils/assets.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool_notifications/eschool_notifications.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_svg/flutter_svg.dart';

class LogoutButton extends StatelessWidget {
  const LogoutButton({super.key});

  static void showLogOutDialog(
    BuildContext context, {
    bool isBeforeHomePage = false,
  }) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(UiUtils.getTranslatedLabel(context, logoutKey)),
        content: Text(
          UiUtils.getTranslatedLabel(context, logoutDialogMessageKey),
        ),
        actions: [
          TextButton(
            child: Text(UiUtils.getTranslatedLabel(context, yesKey)),
            onPressed: () {
              if (!isBeforeHomePage) {
                if (context.read<AuthCubit>().isParent()) {
                  //If parent is logging out then pop the dialog
                  Navigator.of(context).pop();
                }
                NotificationUtility.removeListener(); //remove old notification listeners
              }
              context.read<AuthCubit>().signOut();
              if (!isBeforeHomePage) {
                Navigator.of(context).pop();
              }
              Navigator.of(context).pushReplacementNamed(Routes.auth);
            },
          ),
          TextButton(
            child: Text(UiUtils.getTranslatedLabel(context, noKey)),
            onPressed: () {
              Navigator.of(context).pop();
            },
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    return Align(
      alignment: Alignment.bottomCenter,
      child: SizedBox(
        width: MediaQuery.sizeOf(context).width * 0.4,
        height: 40,
        child: FilledButton.icon(
          onPressed: () {
            showLogOutDialog(context);
          },
          style: FilledButton.styleFrom(
            backgroundColor: colorScheme.inverseSurface,
            foregroundColor: colorScheme.onInverseSurface,
            shape: const StadiumBorder(),
            padding: const EdgeInsets.symmetric(horizontal: 12),
          ),
          icon: SizedBox(
            height: 16,
            width: 16,
            child: SvgPicture.asset(Assets.logoutIcon),
          ),
          label: Text(
            UiUtils.getTranslatedLabel(context, logoutKey),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(fontSize: 15, fontWeight: FontWeight.normal),
          ),
        ),
      ),
    );
  }
}
