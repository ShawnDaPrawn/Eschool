import 'package:eschool_core/eschool_core.dart';
import 'package:dotted_border/dotted_border.dart';
import 'package:flutter/material.dart';

class StudyMaterialWithDownloadButtonContainer extends StatelessWidget {
  const StudyMaterialWithDownloadButtonContainer({
    required this.boxConstraints,
    required this.studyMaterial,
    super.key,
  });
  final BoxConstraints boxConstraints;
  final StudyMaterial studyMaterial;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 10, bottom: 10),
      child: GestureDetector(
        onTap: () {
          AppShell.of(context).openStudyMaterial(context, studyMaterial);
        },
        child: DottedBorder(
          options: RoundedRectDottedBorderOptions(
            dashPattern: const [10, 10],
            radius: const Radius.circular(10),
            color: Theme.of(
              context,
            ).colorScheme.onSurfaceVariant.withValues(alpha: 0.25),
          ),
          child: Container(
            alignment: Alignment.center,
            padding: const EdgeInsets.symmetric(horizontal: 7.5, vertical: 7.5),
            decoration: BoxDecoration(borderRadius: BorderRadius.circular(10)),
            child: Row(
              children: [
                SizedBox(
                  width: boxConstraints.maxWidth * 0.7,
                  child: Text(
                    studyMaterial.fileName,
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.onSurface,
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                const Spacer(),
                AppShell.of(
                  context,
                ).studyMaterialButton(context, studyMaterial),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
