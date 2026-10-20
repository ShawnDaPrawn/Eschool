import 'package:eschool/data/models/studyMaterial.dart';
import 'package:eschool/utils/assets.dart';
import 'package:eschool/utils/extensions.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';

class DownloadFileButton extends StatelessWidget {
  const DownloadFileButton({required this.studyMaterial, super.key});
  final StudyMaterial studyMaterial;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(15),
      onTap: () {
        UiUtils.viewOrDownloadStudyMaterial(
          context: context,
          storeInExternalStorage: true,
          studyMaterial: studyMaterial,
        );
      },
      child: Container(
        width: 30,
        height: 30,
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.primary,
          shape: BoxShape.circle,
        ),
        child:
            (studyMaterial.fileExtension.toLowerCase() == 'pdf' ||
                studyMaterial.fileExtension.isImage())
            ? const Icon(
                Icons.remove_red_eye_outlined,
                color: Colors.white,
                size: 15,
              )
            : Padding(
                padding: const EdgeInsets.all(8),
                child: SvgPicture.asset(Assets.downloadIcon),
              ),
      ),
    );
  }
}
