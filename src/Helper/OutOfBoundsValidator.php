<?php

declare(strict_types=1);

namespace Nusje2000\PHPStan\Monolith\Helper;

use LogicException;
use Nusje2000\DependencyGraph\DependencyGraph;
use Nusje2000\DependencyGraph\Package\PackageCollection;
use Nusje2000\DependencyGraph\Package\PackageInterface;
use Nusje2000\PHPStan\Monolith\Exception\OutOfBoundsException;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use ReflectionClass;

final class OutOfBoundsValidator
{
    /**
     * @var DependencyGraph
     */
    private $graph;

    public function __construct()
    {
        $cwd = getcwd();

        if (!is_string($cwd)) {
            throw new LogicException('Could not resolve working directory.');
        }

        $this->graph = DependencyGraph::build($cwd);
    }

    public function validate(Scope $scope, Name $reference): void
    {
        $basePackage = $this->getPackageByScope($scope);
        
        if ($basePackage->getName() === $this->graph->getRootPackage()->getName()) {
            return;
        }

        $requiredPackages = $this->getPackagesAssociatedWithClass($reference);
        if ($requiredPackages->isEmpty() || $requiredPackages->contains($basePackage)) {
            return;
        }

        // check if one of the required packages is a dependency of the base package
        foreach ($requiredPackages as $requiredPackage) {
            if ($basePackage->hasDependency($requiredPackage->getName())) {
                return;
            }
        }

        $requiredPackagesString = implode(', ', $this->getPackageNames($requiredPackages));
        $relativePackageLocation = str_replace($this->graph->getRootPath() . DIRECTORY_SEPARATOR, '', $basePackage->getPackageLocation());

        if (1 === $requiredPackages->count()) {
            throw new OutOfBoundsException(sprintf(
                'Out of bound class %s. Add %s to %s',
                $reference,
                $requiredPackagesString,
                $relativePackageLocation . DIRECTORY_SEPARATOR . 'composer.json'
            ));
        }

        throw new OutOfBoundsException(sprintf(
            'Out of bound class %s. Add one of [%s] to %s',
            $reference,
            $requiredPackagesString,
            $relativePackageLocation . DIRECTORY_SEPARATOR . 'composer.json'
        ));
    }

    /**
     * Returns the package definition that is the closest to the provided scope.
     */
    private function getPackageByScope(Scope $scope): PackageInterface
    {
        $file = $scope->getFile();

        /** @var PackageInterface|null $closestPackage */
        $closestPackage = null;

        foreach ($this->graph->getPackages() as $package) {
            // check if start of path matches
            if (0 === strpos($file, $package->getPackageLocation())) {
                // check if path is closer to the actual file location
                if (null === $closestPackage || strlen($package->getPackageLocation()) > strlen($closestPackage->getPackageLocation())) {
                    $closestPackage = $package;
                }
            }
        }

        if (null === $closestPackage) {
            throw new OutOfBoundsException(sprintf('Could not find package associated with file "%s".', $file));
        }

        return $closestPackage;
    }

    /**
     * Returns all packages associated with the given class.
     *
     * One class can be defined in multiple packages, i.e. symfony does this in their monolithic repository.
     * The symfony/finder component in example is defined in symfony/finder and symfony/symfony
     */
    private function getPackagesAssociatedWithClass(Name $name): PackageCollection
    {
        $referencedClass = (string)$name;
        if (!class_exists($referencedClass) && !interface_exists($referencedClass)) {
            return new PackageCollection();
        }

        $reflection = new ReflectionClass($referencedClass);

        $file = $reflection->getFileName();

        if (false === $file) {
            return new PackageCollection();
        }

        // remove phar scheme and make sure that the directory reparator matches that of the current system
        $file = str_replace(['phar://', '/'], ['', DIRECTORY_SEPARATOR], $file);

        $associatedPackages = $this->graph->getPackages()->filter(function (PackageInterface $package) use ($file) {
            return 0 === strpos($file, $package->getPackageLocation()) && $package !== $this->graph->getRootPackage();
        });

        return $associatedPackages;
    }

    /**
     * @return array<int, string>
     */
    private function getPackageNames(PackageCollection $packages): array
    {
        $names = [];
        foreach ($packages as $package) {
            if (!in_array($package->getName(), $names, true)) {
                $names[] = $package->getName();
            }
        }

        return $names;
    }
}
