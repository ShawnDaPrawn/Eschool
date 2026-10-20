import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

class AddMarksContainer extends StatelessWidget {

  const AddMarksContainer({
    required this.title, required this.alias, required this.totalMarks, required this.obtainedMarksTextEditingController, super.key,
    this.isReadOnly,
  });
  final String title;
  final String alias;
  final String totalMarks;
  final TextEditingController obtainedMarksTextEditingController;
  final bool? isReadOnly;

  Widget _buildSubjectNameWithObtainedMarksContainer({
    required BuildContext context,
    required String alias,
    required String studentName,
    required String totalMarks,
    bool? isReadOnly,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 15),
      width: MediaQuery.sizeOf(context).width,
      padding: const EdgeInsets.symmetric(horizontal: 10),
      child: LayoutBuilder(
        builder: (context, boxConstraints) {
          return Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              //
              Container(
                alignment: AlignmentDirectional.centerStart,
                width: boxConstraints.maxWidth * 0.1,
                child: Text(
                  alias,
                  style: TextStyle(
                    color: Theme.of(context).colorScheme.secondary,
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              Container(
                alignment: AlignmentDirectional.centerStart,
                width: boxConstraints.maxWidth * 0.4,
                child: Text(
                  studentName,
                  style: TextStyle(
                    color: Theme.of(context).colorScheme.secondary,
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              Container(
                decoration: BoxDecoration(
                  color: Theme.of(context).colorScheme.surface,
                  borderRadius: BorderRadius.circular(5),
                  border: Border.all(
                    color: Theme.of(
                      context,
                    ).colorScheme.onSurface.withValues(alpha: 0.5),
                  ),
                ),
                width: boxConstraints.maxWidth * 0.22,
                height: 35,
                alignment: Alignment.center,
                child: TextField(
                  inputFormatters: <TextInputFormatter>[
                    //allow only one decimal point (.)
                    FilteringTextInputFormatter.allow(RegExp(r'^\d+\.?\d*')),
                  ],
                  textAlignVertical: TextAlignVertical.center,
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    color: Theme.of(context).colorScheme.secondary,
                    fontSize: 12,
                  ),
                  controller: obtainedMarksTextEditingController,
                  readOnly: isReadOnly ?? false,
                  decoration: const InputDecoration(
                    border: InputBorder.none,
                    contentPadding: EdgeInsets.zero,
                    isDense: true,
                  ),
                  keyboardType: const TextInputType.numberWithOptions(
                    decimal: true,
                  ),
                ),
              ),
              Container(
                alignment: AlignmentDirectional.centerEnd,
                width: boxConstraints.maxWidth * 0.2,
                child: Text(
                  totalMarks,
                  style: TextStyle(
                    color: Theme.of(context).colorScheme.secondary,
                    fontSize: 12,
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return _buildSubjectNameWithObtainedMarksContainer(
      context: context,
      alias: alias,
      studentName: title,
      totalMarks: totalMarks,
      isReadOnly: isReadOnly,
    );
  }
}
