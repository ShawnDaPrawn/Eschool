import 'package:any_link_preview/any_link_preview.dart';
import 'package:eschool_teacher/ui/styles/colors.dart';
import 'package:eschool_teacher/ui/widgets/customImageWidget.dart';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

class TriangleContainer extends StatelessWidget {
  const TriangleContainer({
    super.key,
    this.isFlipped = false,
    this.color = primaryColor,
    this.size = const Size(20, 20),
  });
  final bool isFlipped;
  final Color color;
  final Size size;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: size.width,
      height: size.height,
      child: ClipPath(
        clipper: TriangleClipper(isFlipped),
        child: Container(
          color: color,
        ),
      ),
    );
  }
}

class TriangleClipper extends CustomClipper<Path> {

  TriangleClipper(this.isFlipped);
  final bool isFlipped;

  @override
  Path getClip(Size size) {
    final path = Path();

    if (isFlipped) {
      path.lineTo(0, size.height);
      path.lineTo(size.width, 0);
    } else {
      path.lineTo(size.width, size.height);
      path.lineTo(size.width, 0);
      path.lineTo(0, 0);
    }

    path.close();
    return path;
  }

  @override
  bool shouldReclip(covariant CustomClipper<Path> oldClipper) {
    return false;
  }
}

class LinkPreviw extends StatelessWidget {
  const LinkPreviw({
    required this.snapshot, required this.link, super.key,
  });
  final String link;
  final AsyncSnapshot snapshot;

  @override
  Widget build(BuildContext context) {
    return (snapshot.data as Metadata).image == null &&
            (snapshot.data as Metadata).title == null &&
            (snapshot.data as Metadata).desc == null
        ? const SizedBox.shrink()
        : GestureDetector(
            onTap: () async {
              await launchUrl(
                Uri.parse(link),
                mode: LaunchMode.externalApplication,
              );
            },
            child: Container(
              clipBehavior: Clip.antiAlias,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(12),
                color: pageBackgroundColor,
              ),
              padding: const EdgeInsets.all(8),
              margin: const EdgeInsets.only(bottom: 5),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                mainAxisSize: MainAxisSize.min,
                children: [
                  if ((snapshot.data as Metadata).image != null)
                    Container(
                      padding: const EdgeInsets.symmetric(vertical: 2.5),
                      constraints: const BoxConstraints(maxHeight: 150),
                      child: CustomImageWidget(
                        imagePath: (snapshot.data as Metadata).image!,
                        errorWidget: (context, url, error) =>
                            const SizedBox.shrink(),
                      ),
                    ),
                  if ((snapshot.data as Metadata).title != null)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 2.5),
                      child: Text(
                        (snapshot.data as Metadata).title ?? '',
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                        ),
                      ),
                    ),
                  if ((snapshot.data as Metadata).desc != null)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 2.5),
                      child: Text(
                        (snapshot.data as Metadata).desc ?? '',
                        style: const TextStyle(fontSize: 12),
                      ),
                    ),
                ],
              ),
            ),
          );
  }
}

List replaceLink({required String text}) {
  //This function will make part of text where link starts. we put invisible character so we can split it with it
  final linkPattern = RegExp(
    r'(http(s)?:\/\/.)?(www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&//=]*)',
  );

  ///This is invisible charector [You can replace it with any special chareactor which generally nobody use]
  const String substringIdentifier = '‎';

  ///This will find and add invisible charector in prefix and suffix
  final String splitMapJoin = text.splitMapJoin(
    linkPattern,
    onMatch: (match) {
      return substringIdentifier + match.group(0)! + substringIdentifier;
    },
    onNonMatch: (match) {
      return match;
    },
  );
  //finally we split it with invisible character so it will become list
  return splitMapJoin.split(substringIdentifier);
}

bool isLink(String input) {
  ///This will check if text contains link
  final matcher = RegExp(
    r'(http(s)?:\/\/.)?(www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&//=]*)',
  );
  return matcher.hasMatch(input);
}

List<String> matchAstric(String data) {
  final pattern = RegExp(r'\*(.*?)\*');

  final String mapJoin = data.splitMapJoin(
    pattern,
    onMatch: (p0) {
      return '‎${p0.group(0)!}‎';
    },
    onNonMatch: (p0) {
      return p0;
    },
  );

  return mapJoin.split('‎');
}
