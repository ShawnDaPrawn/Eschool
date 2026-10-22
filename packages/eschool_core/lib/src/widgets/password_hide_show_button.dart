import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';

class PasswordHideShowButton extends StatelessWidget {
  const PasswordHideShowButton({
    required this.hidePassword,
    required this.onTap,
    super.key,
    this.allSidePadding,
  });
  final bool hidePassword;
  final Function onTap;
  final double? allSidePadding;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.all(allSidePadding ?? 12.0),
      child: GestureDetector(
        onTap: () {
          onTap();
        },
        child: SvgPicture.asset(
          hidePassword
              ? SharedAssets.hidePasswordIcon
              : SharedAssets.showPasswordIcon,
          colorFilter: ColorFilter.mode(
            SharedUiUtils.getColorScheme(context).onSurface,
            BlendMode.srcIn,
          ),
        ),
      ),
    );
  }
}
