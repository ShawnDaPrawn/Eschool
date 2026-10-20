import 'package:eschool_teacher/utils/assets.dart';
import 'package:flutter/material.dart';
import 'package:flutter_svg/svg.dart';

class CustomDropdownButtonContainer extends StatelessWidget {
  const CustomDropdownButtonContainer({
    required this.selectedValue, required this.onTap, super.key,
  });
  final String selectedValue;
  final void Function() onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(8),
        child: Container(
          decoration: BoxDecoration(
            border: Border.all(
              color: Theme.of(
                context,
              ).colorScheme.secondary.withValues(alpha: 0.6),
            ),
            borderRadius: BorderRadius.circular(8),
          ),
          padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 8),
          child: Row(
            children: [
              Expanded(
                child: Text(
                  selectedValue,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: Theme.of(context).colorScheme.secondary,
                    fontSize: 14,
                  ),
                ),
              ),
              SizedBox(
                height: 15,
                width: 15,
                child: SvgPicture.asset(Assets.arrowDownIcon),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
