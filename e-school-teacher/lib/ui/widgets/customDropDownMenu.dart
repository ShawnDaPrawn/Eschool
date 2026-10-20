import 'package:eschool_teacher/utils/assets.dart';
import 'package:flutter/material.dart';
import 'package:flutter_svg/svg.dart';

class CustomDropDownItem {
  CustomDropDownItem({required this.index, required this.title});
  int index;
  String title;

  @override
  bool operator ==(Object other) =>
      other is CustomDropDownItem && other.index == index;

  @override
  int get hashCode => index.hashCode;
}

class CustomDropDownMenu extends StatelessWidget {
  const CustomDropDownMenu({
    required this.width,
    required this.onChanged,
    required this.menu,
    required this.currentSelectedItem,
    super.key,
    this.borderRadius,
    this.textStyle,
    this.bottomMargin,
    this.backgroundColor,
    this.height,
  });
  final double width;
  final double? height;
  final List<String> menu;
  final Function(CustomDropDownItem?) onChanged;
  final CustomDropDownItem currentSelectedItem;
  final Color? backgroundColor;
  final double? borderRadius;
  final double? bottomMargin;
  final TextStyle? textStyle;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(bottom: bottomMargin ?? 20),
      padding: const EdgeInsets.symmetric(horizontal: 10),
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: backgroundColor ?? Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(borderRadius ?? 5),
        border: Border.all(
          color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5),
        ),
      ),
      width: width,
      height: height ?? 40,
      child: DropdownButton<CustomDropDownItem>(
        icon: SizedBox(
          height: 15,
          width: 15,
          child: SvgPicture.asset(Assets.arrowDownIcon),
        ),
        underline: const SizedBox(),
        isExpanded: true,
        value: currentSelectedItem,
        style: TextStyle(
          fontWeight: FontWeight.w600,
          fontSize: 13,
          color: Theme.of(context).colorScheme.onSurface,
        ),
        items: menu.isEmpty
            ? [
                DropdownMenuItem<CustomDropDownItem>(
                  value: currentSelectedItem,
                  child: Text(
                    currentSelectedItem.title,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: textStyle,
                  ),
                ),
              ]
            : menu
                  .asMap()
                  .entries
                  .map(
                    (e) => DropdownMenuItem<CustomDropDownItem>(
                      value: CustomDropDownItem(index: e.key, title: e.value),
                      child: Text(
                        e.value,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: textStyle,
                      ),
                    ),
                  )
                  .toList(),
        onChanged: onChanged,
      ),
    );
  }
}
