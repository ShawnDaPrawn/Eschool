import 'dart:io';

import 'package:cached_network_image/cached_network_image.dart';
import 'package:eschool_teacher/ui/widgets/customCircularProgressIndicator.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_svg/svg.dart';

class CustomImageWidget extends StatelessWidget {
  const CustomImageWidget({
    required this.imagePath, super.key,
    this.isFile = false,
    this.isAsset = false,
    this.boxFit = BoxFit.cover,
    this.customLoadingPlaceholder,
    this.progressIndicatorBuilder,
    this.imageBuilder,
    this.errorWidget,
  });
  final bool isFile;
  final bool isAsset;
  final String imagePath;
  final BoxFit boxFit;
  final Widget? customLoadingPlaceholder;
  final ProgressIndicatorBuilder? progressIndicatorBuilder;
  final ImageWidgetBuilder? imageBuilder;
  final LoadingErrorWidgetBuilder? errorWidget;

  @override
  Widget build(BuildContext context) {
    return !isFile && !isAsset //it's network in that case
        ? imagePath.toLowerCase().endsWith('svg')
            ? SvgPicture.network(
                imagePath,
                fit: boxFit,
              )
            : CachedNetworkImage(
                errorWidget: errorWidget ??
                    (context, image, _) => Center(
                          child: Icon(
                            Icons.error,
                            color: Theme.of(context).primaryColor,
                          ),
                        ),
                imageUrl: imagePath,
                fit: boxFit,
                placeholder: progressIndicatorBuilder != null
                    ? null
                    : (context, url) =>
                        customLoadingPlaceholder ??
                        Center(
                          child: CustomCircularProgressIndicator(
                            indicatorColor:
                                UiUtils.getColorScheme(context).primary,
                          ),
                        ),
                progressIndicatorBuilder: progressIndicatorBuilder,
                imageBuilder: imageBuilder,
              )
        : isAsset
            ? imagePath.toLowerCase().endsWith('svg')
                ? SvgPicture.asset(
                    imagePath,
                    fit: boxFit,
                  )
                : Image.asset(
                    imagePath,
                    fit: boxFit,
                  )
            : imagePath.toLowerCase().endsWith('svg')
                ? SvgPicture.file(
                    File(imagePath),
                    fit: boxFit,
                  )
                : Image.file(
                    File(imagePath),
                    fit: boxFit,
                  );
  }
}
