import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';

class SvgButton extends StatelessWidget {

  const SvgButton(
      {required this.onTap, required this.svgIconUrl, super.key,
      this.buttonColor});
  final Function onTap;
  final Color? buttonColor;
  final String svgIconUrl;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () {
        onTap.call();
      },
      child: Container(
        decoration:
            BoxDecoration(border: Border.all(color: Colors.transparent)),
        height: 25,
        width: 25,
        child: SvgPicture.asset(
          svgIconUrl,
          colorFilter: ColorFilter.mode(
              buttonColor ?? Theme.of(context).scaffoldBackgroundColor,
              BlendMode.srcIn),
        ),
      ),
    );
  }
}
