import 'package:eschool_teacher/data/models/studyMaterial.dart';
import 'package:eschool_teacher/utils/assets.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';

class DownloadFileButton extends StatelessWidget {
  const DownloadFileButton({required this.studyMaterial, super.key});
  final StudyMaterial studyMaterial;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(15),
      onTap: () async {
        UiUtils.viewOrDownloadStudyMaterial(
          context: context,
          storeInExternalStorage: true,
          studyMaterial: studyMaterial,
        );
      },
      child: Container(
        width: 30,
        height: 30,
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.primary,
          shape: BoxShape.circle,
        ),
        child: SvgPicture.asset(Assets.downloadIcon),
      ),
    );
  }
}
